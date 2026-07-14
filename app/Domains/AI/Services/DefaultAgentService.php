<?php

declare(strict_types=1);

namespace App\Domains\AI\Services;

use App\Domains\Accounts\Models\OpenRouterCredential;
use App\Domains\AI\Contracts\AgentService;
use App\Domains\AI\DTOs\LlmMessage;
use App\Domains\AI\DTOs\ToolCall;
use App\Domains\AI\Enums\StepStatus;
use App\Domains\AI\Enums\ToolName;
use App\Domains\AI\Exceptions\AgentResumeException;
use App\Domains\AI\Exceptions\InvalidApiKeyException;
use App\Domains\AI\Exceptions\VisionNotSupportedException;
use App\Domains\AI\Tools\ToolExecutor;
use App\Domains\Billing\Contracts\BillingService;
use App\Domains\Billing\Exceptions\MessageLimitReachedException;
use App\Domains\Chat\Contracts\ChatService;
use App\Domains\Chat\Enums\MessageRole;
use App\Domains\Chat\Models\ActionStep;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Chat\Models\Message;
use App\Domains\Stores\Contracts\StoreAdapterFactory;

final class DefaultAgentService implements AgentService
{
    public function __construct(
        private readonly ToolExecutor $executor,
        private readonly StoreAdapterFactory $stores,
        private readonly ChatService $chat,
        private readonly BillingService $billing,
        private readonly AuditLogger $audit,
        private readonly ModelAllowList $modelAllowList,
        private readonly AgentRunLoop $runLoop,
        private readonly AgentMessageHistoryBuilder $messageHistory,
        private readonly AgentMemoryRecorder $memoryRecorder,
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
            throw new VisionNotSupportedException('Selected model does not support vision attachments.');
        }

        $userDto = $this->chat->appendUserMessage($conversationId, $userMessage, $attachmentIds);
        $this->memoryRecorder->recordPreferenceIfPresent($conversation->account_id, $userMessage);
        $assistantMessage = $this->chat->appendAssistantMessage($conversationId, '', $model);
        $this->chat->updateConversationModel($conversationId, $model);

        $messages = $this->messageHistory->build($conversation, $userDto);
        $this->runLoop->run(
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
            throw new AgentResumeException('No agent state to resume.');
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
            throw new AgentResumeException('Pending action step not found.');
        }

        $result = $step->result_summary ?? ['ok' => false, 'error' => 'Unknown result'];

        if ($step->confirmed === false) {
            $result = ['ok' => false, 'error' => 'Merchant declined this action.'];
        }

        $messages = $this->messageHistory->build($conversation);
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

        $this->runLoop->run(
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
            $this->memoryRecorder->recordConfirmedAction(
                accountId: $conversation->account_id,
                tool: $tool,
                args: $step->arguments ?? [],
                actionStepId: $actionStepId,
            );
        }
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
}
