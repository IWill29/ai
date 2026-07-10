<?php

declare(strict_types=1);

namespace App\Domains\Stores\DTOs;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final readonly class PaginatedResult
{
    /**
     * @param  array<int, mixed>  $items
     */
    public function __construct(
        public array $items,
        public ?string $nextCursor,
        public bool $hasMore,
    ) {}
}
