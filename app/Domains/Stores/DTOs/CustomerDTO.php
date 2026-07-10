<?php

declare(strict_types=1);

namespace App\Domains\Stores\DTOs;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final readonly class CustomerDTO
{
    public function __construct(
        public string $externalId,
        public ?string $email,
        public ?string $name,
        public int $ordersCount,
        public int $totalSpentMinor,
        public ?string $currency,
    ) {}
}
