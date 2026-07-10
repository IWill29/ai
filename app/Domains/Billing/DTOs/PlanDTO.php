<?php

declare(strict_types=1);

namespace App\Domains\Billing\DTOs;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final readonly class PlanDTO
{
    public function __construct(
        public string $slug,
        public string $name,
        public int $priceCents,
        public string $currency,
        public ?int $storeLimit,
        public ?int $monthlyMessageLimit,
    ) {}
}
