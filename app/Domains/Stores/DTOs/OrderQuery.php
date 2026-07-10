<?php

declare(strict_types=1);

namespace App\Domains\Stores\DTOs;

use DateTimeImmutable;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final readonly class OrderQuery
{
    public function __construct(
        public ?string $fulfillmentStatus = null,
        public ?string $financialStatus = null,
        public ?DateTimeImmutable $placedAfter = null,
        public ?int $minTotalMinor = null,
        public ?string $search = null,
        public int $limit = 25,
        public ?string $cursor = null,
    ) {}
}
