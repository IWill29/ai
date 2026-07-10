<?php

declare(strict_types=1);

namespace App\Domains\Stores\DTOs;

use DateTimeImmutable;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final readonly class OrderDTO
{
    /**
     * @param  array<int, LineItemDTO>  $lineItems
     */
    public function __construct(
        public string $externalId,
        public ?string $orderNumber,
        public ?string $financialStatus,
        public ?string $fulfillmentStatus,
        public int $totalPriceMinor,
        public ?string $currency,
        public ?string $customerExternalId,
        public array $lineItems,
        public ?DateTimeImmutable $placedAt,
    ) {}
}
