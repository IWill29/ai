<?php

declare(strict_types=1);

namespace App\Domains\Chat\Services;

use App\Domains\Chat\Contracts\AttachmentUploadService;
use App\Domains\Chat\DTOs\AttachmentDTO;
use BadMethodCallException;
use Illuminate\Http\UploadedFile;

/**
 * Placeholder until Phase 8 (POST /attachments endpoint).
 */
final class StubAttachmentUploadService implements AttachmentUploadService
{
    public function store(string $accountId, string $userId, UploadedFile $file): AttachmentDTO
    {
        throw new BadMethodCallException('AttachmentUploadService not implemented until Phase 8.');
    }

    public function deletePending(string $accountId, string $attachmentId): void
    {
        throw new BadMethodCallException('AttachmentUploadService not implemented until Phase 8.');
    }

    public function loadForMessage(string $accountId, array $attachmentIds): array
    {
        throw new BadMethodCallException('AttachmentUploadService not implemented until Phase 8.');
    }
}
