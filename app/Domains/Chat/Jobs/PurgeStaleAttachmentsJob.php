<?php

declare(strict_types=1);

namespace App\Domains\Chat\Jobs;

use App\Domains\Chat\Models\MessageAttachment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Support\AttachmentStorage;

final class PurgeStaleAttachmentsJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        MessageAttachment::query()
            ->where('status', 'pending')
            ->where('expires_at', '<', now())
            ->each(function (MessageAttachment $attachment): void {
                AttachmentStorage::disk()->delete($attachment->storage_path);
                $attachment->delete();
            });
    }
}
