<?php

declare(strict_types=1);

namespace App\Domains\Stores\DTOs;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final readonly class VariantDTO
{
    public function __construct(
        public string $externalId,
        public ?string $sku,
        public ?string $title,
        public ?int $priceMinor,
        public ?string $currency,
        public ?int $inventoryQuantity,
    ) {}
}
