<?php

declare(strict_types=1);

namespace App\Domains\AI\Services;

use App\Domains\AI\DTOs\LlmContentPart;
use App\Domains\AI\DTOs\LlmMessage;
use App\Domains\Chat\DTOs\MessageDTO;
use App\Domains\Chat\Models\MessageAttachment;
use Illuminate\Support\Facades\Storage;

final class MultimodalMessageBuilder
{
    public function buildUserMessage(MessageDTO $message): LlmMessage
    {
        $text = $message->content ?? '';

        if ($message->attachments !== []) {
            $ids = implode(', ', array_map(fn ($attachment) => $attachment->id, $message->attachments));
            $text .= "\n\n[Attached product images — use image_attachment_ids on update_product: {$ids}]";
        }

        if ($message->attachments === []) {
            return new LlmMessage(role: 'user', content: $text);
        }

        /** @var array<int, LlmContentPart> $parts */
        $parts = [new LlmContentPart(type: 'text', text: $text)];

        foreach ($message->attachments as $attachment) {
            $parts[] = new LlmContentPart(
                type: 'image_url',
                imageUrl: $this->toDataUrl($attachment->id),
                detail: 'auto',
            );
        }

        return new LlmMessage(role: 'user', contentParts: $parts);
    }

    private function toDataUrl(string $attachmentId): string
    {
        $attachment = MessageAttachment::query()->findOrFail($attachmentId);
        $binary = Storage::disk((string) config('agent.attachment.disk', 'attachments'))
            ->get($attachment->storage_path);

        return 'data:'.$attachment->mime_type.';base64,'.base64_encode($binary);
    }
}
