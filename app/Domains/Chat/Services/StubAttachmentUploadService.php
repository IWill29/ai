<?php

declare(strict_types=1);

namespace App\Domains\Chat\Services;

use App\Domains\Chat\Contracts\AttachmentUploadService;
use App\Domains\Chat\DTOs\AttachmentDTO;
use App\Domains\Shared\Concerns\DefersImplementation;
use Illuminate\Http\UploadedFile;

/**
 * Placeholder until Phase 8 (POST /attachments endpoint).
 */
final class StubAttachmentUploadService implements AttachmentUploadService
{
    use DefersImplementation;

    public function store(string $accountId, string $userId, UploadedFile $file): AttachmentDTO
    {
        $this->notImplemented('AttachmentUploadService');
    }

    public function deletePending(string $accountId, string $attachmentId): void
    {
        $this->notImplemented('AttachmentUploadService');
    }

    public function loadForMessage(string $accountId, array $attachmentIds): array
    {
        $this->notImplemented('AttachmentUploadService');
    }
}
