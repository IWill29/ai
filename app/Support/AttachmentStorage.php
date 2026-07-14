<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

final class AttachmentStorage
{
    public static function diskName(): string
    {
        return (string) config('agent.attachment.disk', 'attachments');
    }

    public static function disk(): Filesystem
    {
        return Storage::disk(self::diskName());
    }
}
