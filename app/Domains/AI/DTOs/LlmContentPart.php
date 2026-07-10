<?php

declare(strict_types=1);

namespace App\Domains\AI\DTOs;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final readonly class LlmContentPart
{
    public function __construct(
        public string $type,
        public ?string $text = null,
        public ?string $imageUrl = null,
        public ?string $detail = null,
    ) {}
}
