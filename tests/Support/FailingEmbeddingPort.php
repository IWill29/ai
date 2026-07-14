<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domains\AI\Contracts\EmbeddingPort;
use App\Domains\AI\Exceptions\EmbeddingUnavailableException;

final class FailingEmbeddingPort implements EmbeddingPort
{
    public function embed(string $apiKey, string $text, string $accountId = ''): array
    {
        throw new EmbeddingUnavailableException('Embeddings unavailable in test.');
    }
}
