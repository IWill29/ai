<?php

declare(strict_types=1);

namespace App\Domains\AI\Services;

use App\Domains\Accounts\Models\OpenRouterCredential;
use App\Domains\AI\Contracts\AgentLlmPort;
use App\Domains\AI\Contracts\AgentService;
use App\Domains\AI\Contracts\MemoryService;
use App\Domains\AI\DTOs\LlmMessage;
use App\Domains\AI\DTOs\LlmResponse;
use App\Domains\AI\DTOs\ToolCall;
use App\Domains\AI\Enums\StepStatus;
use App\Domains\AI\Enums\ToolName;
use App\Domains\AI\Exceptions\InvalidApiKeyException;
use App\Domains\AI\Tools\ToolExecutor;
use App\Domains\AI\Tools\ToolRegistry;
use App\Domains\Billing\Contracts\BillingService;
use App\Domains\Billing\Exceptions\MessageLimitReachedException;
use App\Domains\Chat\Contracts\ChatService;
use App\Domains\Chat\DTOs\ActionStepDTO;
use App\Domains\Chat\DTOs\MessageDTO;
use App\Domains\Chat\Enums\MessageRole;
use App\Domains\Chat\Models\ActionStep;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Chat\Models\Message;
use App\Domains\Stores\Contracts\StoreAdapterFactory;

final class DefaultAgentService implements AgentService
{
    public function __construct(
        private readonly AgentLlmPort $llm,
        private readonly ToolRegistry $tools,
        private readonly ToolExecutor $executor,
        private readonly StoreAdapterFactory $stores,
        private readonly ChatService $chat,
        private readonly BillingService $billing,
        private readonly MemoryService $memory,
        private readonly SystemPromptBuilder $prompts,
        private readonly AuditLogger $audit,
        private readonly ModelAllowList $modelAllowList,
        private readonly MultimodalMessageBuilder $multimodal,
        private readonly AttachmentResolver $attachmentResolver,
    ) {}

    public function runWithStream(
        string $conversationId,
        string $userMessage,
        callable $emit,
        array $attachmentIds = [],
    ): void {
        $conversation = Conversation::query()->with('storeConnection')->findOrFail($conversationId);

        if (! $this->billing->canSendMessage($conversation->account_id)) {
            throw new MessageLimitReachedException('Monthly agent message limit reached.');
        }

        $apiKey = $this->resolveApiKey($conversation->account_id);
        $model = $this->resolveModel($conversation);

        if ($attachmentIds !== [] && ! $this->modelSupportsVision($model)) {
            throw new \InvalidArgumentException('Selected model does not support vision attachments.');
        }

        $userDto = $this->chat->appendUserMessage($conversationId, $userMessage, $attachmentIds);
        $assistantMessage = $this->chat->appendAssistantMessage($conversationId, '', $model);
        $this->chat->updateConversationModel($conversationId, $model);

        $messages = $this->buildMessageHistory($conversation, $userDto);
        $this->runLoop(
            conversation: $conversation,
            assistantMessageId: $assistantMessage->id,
            apiKey: $apiKey,
            model: $model,
            messages: $messages,
            emit: $emit,
            incrementOnComplete: true,
        );
    }

    public function resumeWithStream(string $conversationId, callable $emit): void
    {
        $conversation = Conversation::query()->with('storeConnection')->findOrFail($conversationId);

        $assistantMessage = Message::query()
            ->where('conversation_id', $conversationId)
            ->where('role', MessageRole::Assistant->value)
            ->whereNotNull('meta->agent_state')
            ->latest('created_at')
            ->firstOrFail();

        $state = $assistantMessage->meta['agent_state'] ?? null;

        if (! is_array($state) || ! isset($state['pending_tool_call'])) {
            throw new \RuntimeException('No agent state to resume.');
        }

        $apiKey = $this->resolveApiKey($conversation->account_id);
        $model = $this->resolveModel($conversation);
        $assistantMessageId = $assistantMessage->id;
        /** @var array{id: string, name: string, arguments: array<string, mixed>} $pending */
        $pending = $state['pending_tool_call'];

        $step = ActionStep::query()
            ->where('message_id', $assistantMessageId)
            ->where('tool_name', $pending['name'])
            ->where('status', '!=', StepStatus::AwaitingConfirmation->value)
            ->latest('step_order')
            ->first();

        if ($step === null) {
            throw new \RuntimeException('Pending action step not found.');
        }

        $result = $step->result_summary ?? ['ok' => false, 'error' => 'Unknown result'];

        if ($step->confirmed === false) {
            $result = ['ok' => false, 'error' => 'Merchant declined this action.'];
        }

        $messages = $this->buildMessageHistory($conversation);
        $messages[] = new LlmMessage(
            role: MessageRole::Assistant->value,
            content: $assistantMessage->content,
            toolCalls: [
                new ToolCall(
                    id: $pending['id'],
                    name: $pending['name'],
                    arguments: $pending['arguments'],
                ),
            ],
        );
        $messages[] = new LlmMessage(
            role: MessageRole::Tool->value,
            content: json_encode($result, JSON_THROW_ON_ERROR),
            toolCallId: $pending['id'],
        );

        $assistantMessage->update([
            'meta' => array_merge($assistantMessage->meta ?? [], ['agent_state' => null]),
        ]);

        $this->runLoop(
            conversation: $conversation,
            assistantMessageId: $assistantMessageId,
            apiKey: $apiKey,
            model: $model,
            messages: $messages,
            emit: $emit,
            incrementOnComplete: true,
        );
    }

    public function resolveConfirmation(string $actionStepId, bool $confirmed): void
    {
        $step = ActionStep::query()->with('message.conversation.storeConnection')->findOrFail($actionStepId);
        $conversation = $step->message->conversation;

        $step->update(['confirmed' => $confirmed]);

        if (! $confirmed) {
            $step->update(['status' => StepStatus::Failed->value, 'result_summary' => ['ok' => false, 'error' => 'Declined by merchant']]);

            return;
        }

        $tool = ToolName::from($step->tool_name);
        $store = $conversation->storeConnection;
        $storePort = $this->stores->for($store);

        $start = hrtime(true);
        $result = $this->executor->execute(
            store: $store,
            storePort: $storePort,
            accountId: $conversation->account_id,
            tool: $tool,
            args: $step->arguments ?? [],
        );
        $durationMs = (int) ((hrtime(true) - $start) / 1_000_000);

        $step->update([
            'status' => $result['ok'] ? StepStatus::Done->value : StepStatus::Failed->value,
            'result_summary' => $result,
            'duration_ms' => $durationMs,
        ]);

        if ($result['ok']) {
            $this->audit->logWrite($step, $result);
        }
    }

    /**
     * @param  array<int, LlmMessage>  $messages
     * @param  callable(string, array<string, mixed>): void  $emit
     */
    private function runLoop(
        Conversation $conversation,
        string $assistantMessageId,
        string $apiKey,
        string $model,
        array $messages,
        callable $emit,
        bool $incrementOnComplete,
    ): void {
        $store = $conversation->storeConnection;
        $storePort = null;
        $toolDefs = $this->tools->all();
        $fallbacks = $this->modelAllowList->fallbacksFor($model);
        $stepOrder = ActionStep::query()->where('message_id', $assistantMessageId)->max('step_order') ?? 0;
        $iterations = 0;
        $finalContent = '';
        $finalResponse = null;

        while ($iterations < (int) config('openrouter.max_iterations', 10)) {
            $iterations++;

            $response = $this->llm->stream(
                apiKey: $apiKey,
                model: $model,
                messages: $messages,
                tools: $toolDefs,
                onDelta: fn (string $delta) => $emit('text_delta', ['content' => $delta]),
                accountId: $conversation->account_id,
                fallbackModels: $fallbacks,
            );

            $finalResponse = $response;
            $finalContent = ($finalContent !== '' ? $finalContent : '').($response->content ?? '');
            $messages[] = $this->toAssistantLlmMessage($response);

            $this->chat->updateAssistantContent($assistantMessageId, $finalContent, [
                'tool_calls' => array_map(
                    fn (ToolCall $toolCall) => [
                        'id' => $toolCall->id,
                        'name' => $toolCall->name,
                        'arguments' => $toolCall->arguments,
                    ],
                    $response->toolCalls,
                ),
            ]);

            if ($response->toolCalls === []) {
                break;
            }

            $readCalls = [];
            $writeCalls = [];

            foreach ($response->toolCalls as $toolCall) {
                if (ToolName::from($toolCall->name)->isWrite()) {
                    $writeCalls[] = $toolCall;
                } else {
                    $readCalls[] = $toolCall;
                }
            }

            foreach ($readCalls as $toolCall) {
                $stepOrder++;
                $tool = ToolName::from($toolCall->name);

                $emit('step_started', [
                    'step_order' => $stepOrder,
                    'tool' => $tool->value,
                    'arguments' => $toolCall->arguments,
                    'is_write' => false,
                ]);

                $start = hrtime(true);
                $result = $this->executor->execute(
                    store: $store,
                    storePort: null,
                    accountId: $conversation->account_id,
                    tool: $tool,
                    args: $toolCall->arguments,
                );
                $durationMs = (int) ((hrtime(true) - $start) / 1_000_000);

                $this->chat->recordActionStep($assistantMessageId, new ActionStepDTO(
                    id: '',
                    stepOrder: $stepOrder,
                    toolName: $tool->value,
                    arguments: array_merge($toolCall->arguments, ['_tool_call_id' => $toolCall->id]),
                    targetPlatform: $store->platform,
                    status: $result['ok'] ? StepStatus::Done->value : StepStatus::Failed->value,
                    isWrite: false,
                    confirmed: null,
                    resultSummary: $result,
                    durationMs: $durationMs,
                ));

                $emit('step_done', [
                    'step_order' => $stepOrder,
                    'tool' => $tool->value,
                    'status' => $result['ok'] ? 'done' : 'failed',
                    'duration_ms' => $durationMs,
                    'summary' => $result,
                ]);

                $messages[] = new LlmMessage(
                    role: MessageRole::Tool->value,
                    content: json_encode($result, JSON_THROW_ON_ERROR),
                    toolCallId: $toolCall->id,
                );
            }

            if ($writeCalls !== []) {
                $toolCall = $writeCalls[0];
                $tool = ToolName::from($toolCall->name);
                $stepOrder++;

                $emit('step_started', [
                    'step_order' => $stepOrder,
                    'tool' => $tool->value,
                    'arguments' => $toolCall->arguments,
                    'is_write' => true,
                ]);

                $stepId = $this->chat->recordActionStep($assistantMessageId, new ActionStepDTO(
                    id: '',
                    stepOrder: $stepOrder,
                    toolName: $tool->value,
                    arguments: array_merge($toolCall->arguments, ['_tool_call_id' => $toolCall->id]),
                    targetPlatform: $store->platform,
                    status: StepStatus::AwaitingConfirmation->value,
                    isWrite: true,
                    confirmed: null,
                    resultSummary: null,
                    durationMs: null,
                ));

                $assistantMessage = Message::query()->findOrFail($assistantMessageId);
                $assistantMessage->update([
                    'meta' => array_merge($assistantMessage->meta ?? [], [
                        'agent_state' => [
                            'pending_tool_call' => [
                                'id' => $toolCall->id,
                                'name' => $toolCall->name,
                                'arguments' => $toolCall->arguments,
                            ],
                        ],
                    ]),
                ]);

                $emit('confirmation_required', [
                    'action_step_id' => $stepId,
                    'tool' => $tool->value,
                    'arguments' => $toolCall->arguments,
                    'description' => $this->describeWriteAction($tool, $toolCall->arguments),
                    'image_previews' => $this->attachmentResolver->imagePreviews($conversation->account_id, $toolCall->arguments),
                    'description_preview' => $toolCall->arguments['description'] ?? null,
                ]);

                $emit('done', ['status' => 'awaiting_confirmation']);

                return;
            }
        }

        if ($iterations >= (int) config('openrouter.max_iterations', 10)) {
            $emit('warning', ['message' => 'Agent reached the step limit. Try a simpler request.']);
        }

        if ($finalResponse !== null) {
            $this->chat->updateAssistantContent($assistantMessageId, $finalContent, [
                'prompt_tokens' => $finalResponse->promptTokens,
                'completion_tokens' => $finalResponse->completionTokens,
                'finish_reason' => $finalResponse->finishReason,
            ]);
        }

        if ($incrementOnComplete) {
            $this->billing->incrementMessageCount($conversation->account_id);
        }

        Message::query()->whereKey($assistantMessageId)->update([
            'meta->agent_state' => null,
        ]);

        $emit('done', ['status' => 'completed']);
    }

    /** @return array<int, LlmMessage> */
    private function buildMessageHistory(Conversation $conversation, ?MessageDTO $latestUser = null): array
    {
        $memories = $this->memory->recall($conversation->account_id, '');
        $messages = [
            new LlmMessage(
                role: MessageRole::System->value,
                content: $this->prompts->build($conversation->storeConnection, $memories),
            ),
        ];

        $history = $this->chat->getHistory($conversation->id);

        foreach ($history as $message) {
            if ($latestUser !== null && $message->id === $latestUser->id) {
                $messages[] = $this->multimodal->buildUserMessage($latestUser);

                continue;
            }

            if ($message->role === MessageRole::User->value) {
                $messages[] = new LlmMessage(role: 'user', content: $message->content ?? '');

                continue;
            }

            if ($message->role === MessageRole::Assistant->value) {
                /** @var array<int, array{id?: string, name?: string, arguments?: array<string, mixed>}> $storedToolCalls */
                $storedToolCalls = [];

                $messageModel = Message::query()->find($message->id);

                if ($messageModel !== null && is_array($messageModel->meta['tool_calls'] ?? null)) {
                    foreach ($messageModel->meta['tool_calls'] as $stored) {
                        if (! is_array($stored)) {
                            continue;
                        }

                        $storedToolCalls[] = new ToolCall(
                            id: (string) ($stored['id'] ?? ''),
                            name: (string) ($stored['name'] ?? ''),
                            arguments: is_array($stored['arguments'] ?? null) ? $stored['arguments'] : [],
                        );
                    }
                }

                $messages[] = new LlmMessage(
                    role: 'assistant',
                    content: $message->content,
                    toolCalls: $storedToolCalls,
                );

                foreach ($message->actionSteps as $step) {
                    if ($step->status === StepStatus::AwaitingConfirmation->value || $step->resultSummary === null) {
                        continue;
                    }

                    $toolCallId = is_string($step->arguments['_tool_call_id'] ?? null)
                        ? $step->arguments['_tool_call_id']
                        : $step->id;

                    $messages[] = new LlmMessage(
                        role: MessageRole::Tool->value,
                        content: json_encode($step->resultSummary, JSON_THROW_ON_ERROR),
                        toolCallId: $toolCallId,
                    );
                }
            }
        }

        return $messages;
    }

    private function toAssistantLlmMessage(LlmResponse $response): LlmMessage
    {
        return new LlmMessage(
            role: MessageRole::Assistant->value,
            content: $response->content,
            toolCalls: $response->toolCalls,
        );
    }

    private function resolveApiKey(string $accountId): string
    {
        $credential = OpenRouterCredential::query()->where('account_id', $accountId)->first();

        if ($credential === null || $credential->validated_at === null) {
            throw new InvalidApiKeyException('OpenRouter API key is required before using the agent.');
        }

        return $credential->api_key;
    }

    private function resolveModel(Conversation $conversation): string
    {
        $model = $conversation->model
            ?? OpenRouterCredential::query()->where('account_id', $conversation->account_id)->value('default_model')
            ?? (string) config('openrouter.default_model');

        $this->modelAllowList->assertAllowed($model);

        return $model;
    }

    private function modelSupportsVision(string $model): bool
    {
        return in_array($model, $this->modelAllowList->all(), true);
    }

    /** @param array<string, mixed> $args */
    private function describeWriteAction(ToolName $tool, array $args): string
    {
        return match ($tool) {
            ToolName::UpdateProduct => $this->describeProductUpdate($args),
            ToolName::UpdateOrder => $this->describeOrderUpdate($args),
            default => ucfirst(str_replace('_', ' ', $tool->value)).' on '.($args['external_id'] ?? 'target'),
        };
    }

    /** @param array<string, mixed> $args */
    private function describeProductUpdate(array $args): string
    {
        $parts = ['Update product '.($args['external_id'] ?? '')];

        if (isset($args['title'])) {
            $parts[] = 'title → '.$args['title'];
        }

        if (isset($args['description'])) {
            $preview = mb_strlen((string) $args['description']) > 120
                ? mb_substr((string) $args['description'], 0, 120).'…'
                : (string) $args['description'];
            $parts[] = 'description → '.$preview;
        }

        if (isset($args['status'])) {
            $parts[] = 'status → '.$args['status'];
        }

        if (! empty($args['image_attachment_ids']) && is_array($args['image_attachment_ids'])) {
            $parts[] = 'add '.count($args['image_attachment_ids']).' image(s)';
        }

        return implode('; ', $parts);
    }

    /** @param array<string, mixed> $args */
    private function describeOrderUpdate(array $args): string
    {
        $parts = ['Update order '.($args['external_id'] ?? '')];

        foreach (['status', 'note', 'tracking_number', 'tracking_company'] as $field) {
            if (isset($args[$field])) {
                $parts[] = "{$field} → {$args[$field]}";
            }
        }

        if (isset($args['tags']) && is_array($args['tags'])) {
            $parts[] = 'tags → '.implode(', ', $args['tags']);
        }

        return implode('; ', $parts);
    }
}
