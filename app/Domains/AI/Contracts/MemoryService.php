<?php

declare(strict_types=1);

namespace App\Domains\AI\Contracts;

/**
 * Per-account semantic memory — embeds and vector retrieval (ADR 031).
 * Phase 9 swaps StubMemoryService → VectorMemoryService.
 */
interface MemoryService
{
    /**
     * Store a memory item for an account.
     *
     * @param  array<string, mixed>  $meta
     */
    public function remember(string $accountId, string $content, array $meta = []): void;

    /**
     * Retrieve the most relevant memories for a query.
     *
     * @return array<int, array{content: string, meta: array<string, mixed>}>
     */
    public function recall(string $accountId, string $query, int $limit = 5): array;
}
