<?php

declare(strict_types=1);

namespace App\Domains\AI\Services;

use App\Domains\AI\Contracts\AgentLlmPort;
use App\Domains\AI\DTOs\LlmMessage;
use App\Domains\AI\DTOs\LlmResponse;
use App\Domains\AI\DTOs\ToolCall;
use App\Domains\AI\Enums\StepStatus;
use App\Domains\AI\Enums\ToolName;
use App\Domains\AI\Tools\ToolExecutor;
use App\Domains\AI\Tools\ToolRegistry;
use App\Domains\Billing\Contracts\BillingService;
use App\Domains\Chat\Contracts\ChatService;
use App\Domains\Chat\DTOs\ActionStepDTO;
use App\Domains\Chat\Enums\MessageRole;
use App\Domains\Chat\Models\ActionStep;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Chat\Models\Message;
use App\Domains\Stores\Models\StoreConnection;

final class AgentRunLoop
{
    public function __construct(
        private readonly AgentLlmPort $llm,
        private readonly ToolRegistry $tools,
        private readonly ToolExecutor $executor,
        private readonly ChatService $chat,
        private readonly BillingService $billing,
        private readonly ModelAllowList $modelAllowList,
        private readonly AttachmentResolver $attachmentResolver,
        private readonly WriteActionDescriber $writeActionDescriber,
    ) {}

    /**
     * @param  array<int, LlmMessage>  $messages
     * @param  callable(string, array<string, mixed>): void  $emit
     */
    public function run(
        Conversation $conversation,
        string $assistantMessageId,
        string $apiKey,
        string $model,
        array $messages,
        callable $emit,
        bool $incrementOnComplete,
    ): void {
        $store = $conversation->storeConnection;
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

            [$readCalls, $writeCalls] = $this->partitionToolCalls($response->toolCalls);

            foreach ($readCalls as $toolCall) {
                $stepOrder++;
                $messages[] = $this->executeReadTool(
                    store: $store,
                    accountId: $conversation->account_id,
                    assistantMessageId: $assistantMessageId,
                    toolCall: $toolCall,
                    stepOrder: $stepOrder,
                    emit: $emit,
                );
            }

            if ($writeCalls !== []) {
                $this->pauseForWriteConfirmation(
                    store: $store,
                    conversation: $conversation,
                    assistantMessageId: $assistantMessageId,
                    toolCall: $writeCalls[0],
                    stepOrder: ++$stepOrder,
                    emit: $emit,
                );

                return;
            }
        }

        $this->finalize(
            conversation: $conversation,
            assistantMessageId: $assistantMessageId,
            finalContent: $finalContent,
            finalResponse: $finalResponse,
            iterations: $iterations,
            incrementOnComplete: $incrementOnComplete,
            emit: $emit,
        );
    }

    /** @param array<int, ToolCall> $toolCalls @return array{0: array<int, ToolCall>, 1: array<int, ToolCall>} */
    private function partitionToolCalls(array $toolCalls): array
    {
        $readCalls = [];
        $writeCalls = [];

        foreach ($toolCalls as $toolCall) {
            if (ToolName::from($toolCall->name)->isWrite()) {
                $writeCalls[] = $toolCall;
            } else {
                $readCalls[] = $toolCall;
            }
        }

        return [$readCalls, $writeCalls];
    }

    /** @param callable(string, array<string, mixed>): void  $emit */
    private function executeReadTool(
        StoreConnection $store,
        string $accountId,
        string $assistantMessageId,
        ToolCall $toolCall,
        int $stepOrder,
        callable $emit,
    ): LlmMessage {
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
            accountId: $accountId,
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

        return new LlmMessage(
            role: MessageRole::Tool->value,
            content: json_encode($result, JSON_THROW_ON_ERROR),
            toolCallId: $toolCall->id,
        );
    }

    /** @param callable(string, array<string, mixed>): void  $emit */
    private function pauseForWriteConfirmation(
        StoreConnection $store,
        Conversation $conversation,
        string $assistantMessageId,
        ToolCall $toolCall,
        int $stepOrder,
        callable $emit,
    ): void {
        $tool = ToolName::from($toolCall->name);

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
            'description' => $this->writeActionDescriber->describe($tool, $toolCall->arguments),
            'image_previews' => $this->attachmentResolver->imagePreviews($conversation->account_id, $toolCall->arguments),
            'description_preview' => $toolCall->arguments['description'] ?? null,
        ]);

        $emit('done', ['status' => 'awaiting_confirmation']);
    }

    /** @param callable(string, array<string, mixed>): void  $emit */
    private function finalize(
        Conversation $conversation,
        string $assistantMessageId,
        string $finalContent,
        ?LlmResponse $finalResponse,
        int $iterations,
        bool $incrementOnComplete,
        callable $emit,
    ): void {
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

    private function toAssistantLlmMessage(LlmResponse $response): LlmMessage
    {
        return new LlmMessage(
            role: MessageRole::Assistant->value,
            content: $response->content,
            toolCalls: $response->toolCalls,
        );
    }
}
