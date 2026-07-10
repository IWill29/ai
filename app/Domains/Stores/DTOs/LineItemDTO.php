<?php

declare(strict_types=1);

namespace App\Domains\Stores\DTOs;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final readonly class LineItemDTO
{
    public function __construct(
        public string $externalId,
        public string $title,
        public int $quantity,
        public int $priceMinor,
        public ?string $currency,
    ) {}
}
