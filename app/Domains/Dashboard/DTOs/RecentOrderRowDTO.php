<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\DTOs;

use DateTimeImmutable;
use JsonSerializable;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final readonly class RecentOrderRowDTO implements JsonSerializable
{
    public function __construct(
        public string $externalId,
        public ?string $orderNumber,
        public int $totalPriceMinor,
        public string $currency,
        public ?string $financialStatus,
        public ?string $fulfillmentStatus,
        public DateTimeImmutable $placedAt,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'externalId' => $this->externalId,
            'orderNumber' => $this->orderNumber,
            'totalPriceMinor' => $this->totalPriceMinor,
            'currency' => $this->currency,
            'financialStatus' => $this->financialStatus,
            'fulfillmentStatus' => $this->fulfillmentStatus,
            'placedAt' => $this->placedAt->format(DATE_ATOM),
        ];
    }
}
