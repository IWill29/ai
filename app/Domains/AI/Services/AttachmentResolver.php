<?php

declare(strict_types=1);

namespace App\Domains\AI\Services;

use App\Domains\Chat\Models\MessageAttachment;
use App\Domains\Stores\DTOs\ProductImageInput;
use Illuminate\Support\Facades\Storage;

final class AttachmentResolver
{
    /** @return array<int, ProductImageInput> */
    public function resolveForStore(string $accountId, array $attachmentIds): array
    {
        if ($attachmentIds === []) {
            return [];
        }

        $attachments = MessageAttachment::query()
            ->where('account_id', $accountId)
            ->whereIn('id', $attachmentIds)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->get();

        if ($attachments->count() !== count($attachmentIds)) {
            throw new \InvalidArgumentException('One or more attachment IDs are invalid or expired.');
        }

        return $attachments
            ->map(fn (MessageAttachment $attachment) => new ProductImageInput(
                localPath: Storage::disk('local')->path($attachment->storage_path),
                mimeType: $attachment->mime_type,
                filename: $attachment->filename,
            ))
            ->all();
    }

    /** @param array<int, string> $attachmentIds */
    public function markConsumed(string $accountId, array $attachmentIds): void
    {
        $attachments = MessageAttachment::query()
            ->where('account_id', $accountId)
            ->whereIn('id', $attachmentIds)
            ->get();

        foreach ($attachments as $attachment) {
            Storage::disk('local')->delete($attachment->storage_path);
            $attachment->update(['status' => 'consumed']);
        }
    }

    /** @param array<string, mixed> $args */
    public function imagePreviews(string $accountId, array $args): array
    {
        if (! isset($args['image_attachment_ids']) || ! is_array($args['image_attachment_ids'])) {
            return [];
        }

        return MessageAttachment::query()
            ->where('account_id', $accountId)
            ->whereIn('id', $args['image_attachment_ids'])
            ->get()
            ->map(fn (MessageAttachment $attachment) => route('attachments.preview', $attachment))
            ->all();
    }
}
