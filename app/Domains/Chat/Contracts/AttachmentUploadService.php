<?php

declare(strict_types=1);

namespace App\Domains\Chat\Contracts;

use App\Domains\Chat\DTOs\AttachmentDTO;
use Illuminate\Http\UploadedFile;

/**
 * Temp attachment upload for product image writes (ADR 040).
 */
interface AttachmentUploadService
{
    public function store(string $accountId, string $userId, UploadedFile $file): AttachmentDTO;

    public function deletePending(string $accountId, string $attachmentId): void;

    /**
     * @param  array<int, string>  $attachmentIds
     * @return array<int, AttachmentDTO>
     */
    public function loadForMessage(string $accountId, array $attachmentIds): array;
}
