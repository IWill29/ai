<?php

declare(strict_types=1);

namespace App\Domains\AI\Services;

use App\Domains\AI\Contracts\MemoryService;
use App\Domains\AI\DTOs\LlmMessage;
use App\Domains\AI\DTOs\ToolCall;
use App\Domains\AI\Enums\StepStatus;
use App\Domains\Chat\Contracts\ChatService;
use App\Domains\Chat\DTOs\MessageDTO;
use App\Domains\Chat\Enums\MessageRole;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Chat\Models\Message;

final class AgentMessageHistoryBuilder
{
    public function __construct(
        private readonly ChatService $chat,
        private readonly MemoryService $memory,
        private readonly SystemPromptBuilder $prompts,
        private readonly MultimodalMessageBuilder $multimodal,
    ) {}

    /** @return array<int, LlmMessage> */
    public function build(Conversation $conversation, ?MessageDTO $latestUser = null): array
    {
        $memories = $this->memory->recall($conversation->account_id, '');
        $messages = [
            new LlmMessage(
                role: MessageRole::System->value,
                content: $this->prompts->build($conversation->storeConnection, $memories),
            ),
        ];

        foreach ($this->chat->getHistory($conversation->id) as $message) {
            if ($latestUser !== null && $message->id === $latestUser->id) {
                $messages[] = $this->multimodal->buildUserMessage($latestUser);

                continue;
            }

            if ($message->role === MessageRole::User->value) {
                $messages[] = new LlmMessage(role: 'user', content: $message->content ?? '');

                continue;
            }

            if ($message->role === MessageRole::Assistant->value) {
                array_push($messages, ...$this->buildAssistantMessage($message));
            }
        }

        return $messages;
    }

    /** @return array<int, LlmMessage> */
    private function buildAssistantMessage(MessageDTO $message): array
    {
        $messages = [
            new LlmMessage(
                role: 'assistant',
                content: $message->content,
                toolCalls: $this->storedToolCalls($message->id),
            ),
        ];

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

        return $messages;
    }

    /** @return array<int, ToolCall> */
    private function storedToolCalls(string $messageId): array
    {
        $messageModel = Message::query()->find($messageId);

        if ($messageModel === null || ! is_array($messageModel->meta['tool_calls'] ?? null)) {
            return [];
        }

        $storedToolCalls = [];

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

        return $storedToolCalls;
    }
}
