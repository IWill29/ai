<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domains\AI\Contracts\EmbeddingPort;

final class DeterministicEmbeddingPort implements EmbeddingPort
{
    public function embed(string $apiKey, string $text, string $accountId = ''): array
    {
        $normalized = mb_strtolower(trim($text));
        $vector = array_fill(0, 1536, 0.0);

        if (str_contains($normalized, 'brief') || str_contains($normalized, 'summary')) {
            $vector[0] = 1.0;

            return $vector;
        }

        if (str_contains($normalized, 'fulfill')) {
            $vector[1] = 1.0;

            return $vector;
        }

        $vector[2] = 1.0;

        return $vector;
    }
}
