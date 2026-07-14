<?php

declare(strict_types=1);

namespace App\Domains\AI\Contracts;

/**
 * BYOK OpenRouter embeddings — same merchant key as chat (ADR 031).
 */
interface EmbeddingPort
{
    /**
     * @return array<int, float>
     */
    public function embed(string $apiKey, string $text, string $accountId = ''): array;
}
