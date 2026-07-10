<?php

declare(strict_types=1);

namespace App\Domains\AI\Services;

use App\Domains\AI\Contracts\MemoryService;

/**
 * No-op memory until Phase 9 (VectorMemoryService + pgvector).
 */
final class StubMemoryService implements MemoryService
{
    /** @param  array<string, mixed>  $meta */
    public function remember(string $accountId, string $content, array $meta = []): void
    {
        // no-op until Phase 9
    }

    public function recall(string $accountId, string $query, int $limit = 5): array
    {
        return [];
    }
}
