<?php

declare(strict_types=1);

namespace App\Domains\AI\Services;

use App\Domains\AI\Contracts\EmbeddingPort;
use App\Domains\AI\Exceptions\EmbeddingUnavailableException;
use App\Domains\AI\Exceptions\InvalidApiKeyException;
use Illuminate\Support\Facades\Http;

final class OpenRouterEmbeddingAdapter implements EmbeddingPort
{
    public function embed(string $apiKey, string $text, string $accountId = ''): array
    {
        $body = [
            'model' => (string) config('openrouter.embedding_model'),
            'input' => $text,
            'dimensions' => (int) config('openrouter.embedding_dimensions', 1536),
        ];

        if ($accountId !== '') {
            $body['user'] = $accountId;
        }

        $response = Http::withToken($apiKey)
            ->withHeaders([
                'HTTP-Referer' => (string) config('openrouter.app_url'),
                'X-Title' => (string) config('openrouter.app_name'),
            ])
            ->timeout((int) config('openrouter.embedding_timeout', 30))
            ->post((string) config('openrouter.base_url').'/embeddings', $body);

        if ($response->status() === 401) {
            throw new InvalidApiKeyException('OpenRouter rejected the API key for embeddings.');
        }

        if ($response->failed()) {
            throw new EmbeddingUnavailableException('OpenRouter embeddings HTTP '.$response->status().': '.$response->body());
        }

        $payload = $response->json();
        $embedding = $payload['data'][0]['embedding'] ?? null;

        if (! is_array($embedding) || $embedding === []) {
            throw new EmbeddingUnavailableException('OpenRouter returned an empty embedding.');
        }

        return array_map(static fn (mixed $value): float => (float) $value, $embedding);
    }
}
