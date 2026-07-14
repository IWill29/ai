<?php

declare(strict_types=1);

namespace App\Domains\Chat\Services;

use App\Domains\Billing\Actions\RecordAuditAction;
use App\Domains\Chat\Contracts\AttachmentUploadService;
use App\Domains\Chat\DTOs\AttachmentDTO;
use App\Domains\Chat\Models\MessageAttachment;
use App\Support\ImageFileValidator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class DefaultAttachmentUploadService implements AttachmentUploadService
{
    public function __construct(
        private readonly ImageFileValidator $validator,
        private readonly RecordAuditAction $recordAudit,
    ) {}

    public function store(string $accountId, string $userId, UploadedFile $file): AttachmentDTO
    {
        $this->assertPendingLimit($accountId);

        $mimeType = $this->validator->validate($file);
        $disk = $this->disk();
        $extension = $this->extensionForMime($mimeType, $file);
        $path = sprintf('%s/%s.%s', $accountId, Str::uuid(), $extension);

        Storage::disk($disk)->put($path, $file->getContent());

        $attachment = MessageAttachment::query()->create([
            'account_id' => $accountId,
            'uploaded_by' => $userId,
            'filename' => $file->getClientOriginalName(),
            'mime_type' => $mimeType,
            'size_bytes' => $file->getSize(),
            'storage_path' => $path,
            'status' => 'pending',
            'expires_at' => now()->addHours((int) config('agent.attachment.ttl_hours', 24)),
        ]);

        $this->recordAudit->execute(
            accountId: $accountId,
            userId: (int) $userId,
            storeConnectionId: null,
            action: 'attachment.upload',
            context: [
                'attachment_id' => $attachment->id,
                'filename' => $attachment->filename,
                'mime_type' => $mimeType,
                'size_bytes' => $attachment->size_bytes,
            ],
        );

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

        Storage::disk($this->disk())->delete($attachment->storage_path);
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

    private function assertPendingLimit(string $accountId): void
    {
        $maxFiles = (int) config('agent.attachment.max_files', 5);

        $pendingCount = MessageAttachment::query()
            ->where('account_id', $accountId)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->count();

        if ($pendingCount >= $maxFiles) {
            throw new \InvalidArgumentException('Maximum number of pending attachments reached.');
        }
    }

    private function extensionForMime(string $mimeType, UploadedFile $file): string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => $file->guessExtension() ?? 'bin',
        };
    }

    private function disk(): string
    {
        return (string) config('agent.attachment.disk', 'attachments');
    }
}
