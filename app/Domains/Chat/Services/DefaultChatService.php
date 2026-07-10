<?php

declare(strict_types=1);

namespace App\Domains\Chat\Services;

use App\Domains\Chat\Contracts\ChatService;
use App\Domains\Chat\DTOs\ActionStepDTO;
use App\Domains\Chat\DTOs\ConversationDTO;
use App\Domains\Chat\DTOs\MessageDTO;
use App\Domains\Shared\Concerns\DefersImplementation;

/**
 * Placeholder until Phase 10 (chat persistence + SSE).
 */
final class DefaultChatService implements ChatService
{
    use DefersImplementation;

    public function startConversation(string $accountId, string $userId, ?string $storeConnectionId, ?string $model): ConversationDTO
    {
        $this->notImplemented('ChatService');
    }

    public function appendUserMessage(string $conversationId, string $content, array $attachmentIds = []): MessageDTO
    {
        $this->notImplemented('ChatService');
    }

    public function appendAssistantMessage(string $conversationId, string $content, string $model, array $meta = []): MessageDTO
    {
        $this->notImplemented('ChatService');
    }

    public function recordActionStep(string $messageId, ActionStepDTO $step): void
    {
        $this->notImplemented('ChatService');
    }

    public function getHistory(string $conversationId): array
    {
        $this->notImplemented('ChatService');
    }
}
