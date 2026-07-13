<?php

declare(strict_types=1);

namespace App\Domains\Chat\Services;

use App\Domains\Chat\Contracts\ChatService;
use App\Domains\Chat\DTOs\ActionStepDTO;
use App\Domains\Chat\DTOs\AttachmentDTO;
use App\Domains\Chat\DTOs\ConversationDTO;
use App\Domains\Chat\DTOs\MessageDTO;
use App\Domains\Chat\Enums\MessageRole;
use App\Domains\Chat\Models\ActionStep;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Chat\Models\Message;
use App\Domains\Chat\Models\MessageAttachment;

final class DefaultChatService implements ChatService
{
    public function startConversation(
        string $accountId,
        string $userId,
        ?string $storeConnectionId,
        ?string $model,
    ): ConversationDTO {
        $conversation = Conversation::query()->create([
            'account_id' => $accountId,
            'user_id' => $userId,
            'store_connection_id' => $storeConnectionId,
            'model' => $model,
        ]);

        return $this->toConversationDto($conversation);
    }

    public function appendUserMessage(string $conversationId, string $content, array $attachmentIds = []): MessageDTO
    {
        $conversation = Conversation::query()->findOrFail($conversationId);

        $message = Message::query()->create([
            'conversation_id' => $conversation->id,
            'role' => MessageRole::User->value,
            'content' => $content,
            'meta' => $attachmentIds !== [] ? ['attachment_ids' => $attachmentIds] : null,
        ]);

        if ($attachmentIds !== []) {
            MessageAttachment::query()
                ->where('account_id', $conversation->account_id)
                ->whereIn('id', $attachmentIds)
                ->update(['message_id' => $message->id]);
        }

        return $this->toMessageDto($message->fresh(['attachments', 'actionSteps']));
    }

    public function appendAssistantMessage(
        string $conversationId,
        string $content,
        string $model,
        array $meta = [],
    ): MessageDTO {
        $message = Message::query()->create([
            'conversation_id' => $conversationId,
            'role' => MessageRole::Assistant->value,
            'content' => $content,
            'model' => $model,
            'meta' => $meta === [] ? null : $meta,
        ]);

        return $this->toMessageDto($message);
    }

    public function recordActionStep(string $messageId, ActionStepDTO $step): string
    {
        $created = ActionStep::query()->create([
            'message_id' => $messageId,
            'step_order' => $step->stepOrder,
            'tool_name' => $step->toolName,
            'arguments' => $step->arguments,
            'target_platform' => $step->targetPlatform,
            'status' => $step->status,
            'is_write' => $step->isWrite,
            'confirmed' => $step->confirmed,
            'result_summary' => $step->resultSummary,
            'duration_ms' => $step->durationMs,
        ]);

        return $created->id;
    }

    public function updateAssistantContent(string $messageId, string $content, array $meta = []): void
    {
        $message = Message::query()->findOrFail($messageId);
        $mergedMeta = array_merge($message->meta ?? [], $meta);

        $message->update([
            'content' => $content,
            'meta' => $mergedMeta === [] ? null : $mergedMeta,
        ]);
    }

    public function updateConversationModel(string $conversationId, string $model): void
    {
        Conversation::query()->whereKey($conversationId)->update(['model' => $model]);
    }

    public function getHistory(string $conversationId): array
    {
        return Message::query()
            ->where('conversation_id', $conversationId)
            ->with(['attachments', 'actionSteps'])
            ->orderBy('created_at')
            ->get()
            ->map(fn (Message $message) => $this->toMessageDto($message))
            ->all();
    }

    private function toConversationDto(Conversation $conversation): ConversationDTO
    {
        return new ConversationDTO(
            id: $conversation->id,
            accountId: $conversation->account_id,
            userId: (string) $conversation->user_id,
            storeConnectionId: $conversation->store_connection_id,
            title: $conversation->title,
            model: $conversation->model,
        );
    }

    private function toMessageDto(Message $message): MessageDTO
    {
        return new MessageDTO(
            id: $message->id,
            role: $message->role,
            content: $message->content,
            model: $message->model,
            attachments: $message->attachments
                ->map(fn (MessageAttachment $attachment) => new AttachmentDTO(
                    id: $attachment->id,
                    filename: $attachment->filename,
                    mimeType: $attachment->mime_type,
                    sizeBytes: $attachment->size_bytes,
                    previewUrl: route('attachments.preview', $attachment),
                    status: $attachment->status,
                ))
                ->all(),
            actionSteps: $message->actionSteps
                ->map(fn (ActionStep $step) => new ActionStepDTO(
                    id: $step->id,
                    stepOrder: $step->step_order,
                    toolName: $step->tool_name,
                    arguments: $step->arguments ?? [],
                    targetPlatform: $step->target_platform,
                    status: $step->status,
                    isWrite: $step->is_write,
                    confirmed: $step->confirmed,
                    resultSummary: $step->result_summary,
                    durationMs: $step->duration_ms,
                ))
                ->all(),
        );
    }
}
