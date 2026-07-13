<?php

declare(strict_types=1);

namespace App\Domains\Chat\Services;

use App\Domains\Chat\Contracts\AttachmentUploadService;
use App\Domains\Chat\DTOs\AttachmentDTO;
use App\Domains\Chat\Models\MessageAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class DefaultAttachmentUploadService implements AttachmentUploadService
{
    public function store(string $accountId, string $userId, UploadedFile $file): AttachmentDTO
    {
        $this->assertValid($file);

        $path = sprintf('attachments/%s/%s.%s', $accountId, Str::uuid(), $file->guessExtension() ?? 'bin');
        Storage::disk('local')->put($path, $file->getContent());

        $attachment = MessageAttachment::query()->create([
            'account_id' => $accountId,
            'uploaded_by' => $userId,
            'filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size_bytes' => $file->getSize(),
            'storage_path' => $path,
            'status' => 'pending',
            'expires_at' => now()->addHours((int) config('agent.attachment.ttl_hours', 24)),
        ]);

        return new AttachmentDTO(
            id: $attachment->id,
            filename: $attachment->filename,
            mimeType: $attachment->mime_type,
            sizeBytes: $attachment->size_bytes,
            previewUrl: route('attachments.preview', $attachment),
            status: $attachment->status,
        );
    }

    public function deletePending(string $accountId, string $attachmentId): void
    {
        $attachment = MessageAttachment::query()
            ->where('account_id', $accountId)
            ->whereKey($attachmentId)
            ->where('status', 'pending')
            ->firstOrFail();

        Storage::disk('local')->delete($attachment->storage_path);
        $attachment->delete();
    }

    public function loadForMessage(string $accountId, array $attachmentIds): array
    {
        return MessageAttachment::query()
            ->where('account_id', $accountId)
            ->whereIn('id', $attachmentIds)
            ->get()
            ->map(fn (MessageAttachment $attachment) => new AttachmentDTO(
                id: $attachment->id,
                filename: $attachment->filename,
                mimeType: $attachment->mime_type,
                sizeBytes: $attachment->size_bytes,
                previewUrl: route('attachments.preview', $attachment),
                status: $attachment->status,
            ))
            ->all();
    }

    private function assertValid(UploadedFile $file): void
    {
        $maxBytes = (int) config('agent.attachment.max_size_bytes', 5 * 1024 * 1024);

        if ($file->getSize() > $maxBytes) {
            throw new \InvalidArgumentException('Attachment exceeds the maximum file size.');
        }

        /** @var array<int, string> $allowed */
        $allowed = config('agent.attachment.allowed_mimes', []);

        if (! in_array($file->getMimeType(), $allowed, true)) {
            throw new \InvalidArgumentException('Attachment type is not allowed.');
        }
    }
}
