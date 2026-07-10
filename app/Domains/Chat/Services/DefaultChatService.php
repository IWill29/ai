<?php

declare(strict_types=1);

namespace App\Domains\Chat\Services;

use App\Domains\Chat\Contracts\ChatService;
use App\Domains\Chat\DTOs\ActionStepDTO;
use App\Domains\Chat\DTOs\ConversationDTO;
use App\Domains\Chat\DTOs\MessageDTO;
use BadMethodCallException;

/**
 * Placeholder until Phase 10 (chat persistence + SSE).
 */
final class DefaultChatService implements ChatService
{
    public function startConversation(string $accountId, string $userId, ?string $storeConnectionId, ?string $model): ConversationDTO
    {
        throw new BadMethodCallException('ChatService not implemented until Phase 10.');
    }

    public function appendUserMessage(string $conversationId, string $content, array $attachmentIds = []): MessageDTO
    {
        throw new BadMethodCallException('ChatService not implemented until Phase 10.');
    }

    public function appendAssistantMessage(string $conversationId, string $content, string $model, array $meta = []): MessageDTO
    {
        throw new BadMethodCallException('ChatService not implemented until Phase 10.');
    }

    public function recordActionStep(string $messageId, ActionStepDTO $step): void
    {
        throw new BadMethodCallException('ChatService not implemented until Phase 10.');
    }

    public function getHistory(string $conversationId): array
    {
        throw new BadMethodCallException('ChatService not implemented until Phase 10.');
    }
}
