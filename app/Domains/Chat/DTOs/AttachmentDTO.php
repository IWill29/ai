<?php

declare(strict_types=1);

namespace App\Domains\Chat\DTOs;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final readonly class AttachmentDTO
{
    public function __construct(
        public string $id,
        public string $filename,
        public string $mimeType,
        public int $sizeBytes,
        public string $previewUrl,
        public string $status,
    ) {}
}
