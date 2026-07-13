<?php

declare(strict_types=1);

namespace App\Domains\Chat\Contracts;

use App\Domains\Chat\DTOs\ActionStepDTO;
use App\Domains\Chat\DTOs\ConversationDTO;
use App\Domains\Chat\DTOs\MessageDTO;

/**
 * Conversation persistence and action trace — the public Chat domain API.
 */
interface ChatService
{
    public function startConversation(string $accountId, string $userId, ?string $storeConnectionId, ?string $model): ConversationDTO;

    /** @param  array<int, string>  $attachmentIds */
    public function appendUserMessage(string $conversationId, string $content, array $attachmentIds = []): MessageDTO;

    /** @param  array<string, mixed>  $meta */
    public function appendAssistantMessage(string $conversationId, string $content, string $model, array $meta = []): MessageDTO;

    public function recordActionStep(string $messageId, ActionStepDTO $step): string;

    /** @param array<string, mixed> $meta */
    public function updateAssistantContent(string $messageId, string $content, array $meta = []): void;

    public function updateConversationModel(string $conversationId, string $model): void;

    /** @return array<int, MessageDTO> */
    public function getHistory(string $conversationId): array;
}
