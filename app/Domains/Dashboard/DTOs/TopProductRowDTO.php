<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\DTOs;

use JsonSerializable;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final readonly class TopProductRowDTO implements JsonSerializable
{
    public function __construct(
        public string $externalId,
        public string $title,
        public int $unitsSold,
        public int $revenueMinor,
        public string $currency,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'externalId' => $this->externalId,
            'title' => $this->title,
            'unitsSold' => $this->unitsSold,
            'revenueMinor' => $this->revenueMinor,
            'currency' => $this->currency,
        ];
    }
}
