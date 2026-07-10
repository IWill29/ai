<?php

declare(strict_types=1);

namespace App\Domains\Stores\DTOs;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final readonly class ProductImageInput
{
    public function __construct(
        public string $localPath,
        public string $mimeType,
        public string $filename,
    ) {}
}
