<?php

declare(strict_types=1);

namespace App\Domains\AI\Services;

use App\Domains\Accounts\Models\OpenRouterCredential;
use App\Domains\AI\Contracts\EmbeddingPort;
use App\Domains\AI\Contracts\MemoryService;
use App\Domains\AI\Models\AgentMemory;
use Illuminate\Support\Facades\Log;
use Pgvector\Laravel\Distance;
use Pgvector\Laravel\Vector;

final class VectorMemoryService implements MemoryService
{
    public function __construct(
        private readonly EmbeddingPort $embeddings,
    ) {}

    /** @param array<string, mixed> $meta */
    public function remember(string $accountId, string $content, array $meta = []): void
    {
        $content = trim($content);

        if ($content === '') {
            return;
        }

        $apiKey = $this->resolveApiKey($accountId);

        if ($apiKey === null) {
            return;
        }

        try {
            $vector = new Vector($this->embeddings->embed($apiKey, $content, $accountId));
        } catch (\Throwable $exception) {
            Log::warning('agent.memory.remember_failed', [
                'account_id' => $accountId,
                'message' => $exception->getMessage(),
            ]);

            return;
        }

        AgentMemory::query()->create([
            'account_id' => $accountId,
            'content' => $content,
            'embedding' => $vector,
            'meta' => $meta === [] ? null : $meta,
        ]);
    }

    public function recall(string $accountId, string $query, int $limit = 5): array
    {
        $query = trim($query);
        $results = [];

        if ($query !== '' && $limit > 0) {
            $apiKey = $this->resolveApiKey($accountId);

            if ($apiKey !== null) {
                try {
                    $embedding = $this->embeddings->embed($apiKey, $query, $accountId);

                    $results = AgentMemory::query()
                        ->where('account_id', $accountId)
                        ->nearestNeighbors('embedding', $embedding, Distance::Cosine)
                        ->take($limit)
                        ->get()
                        ->map(static fn (AgentMemory $memory): array => [
                            'content' => $memory->content,
                            'meta' => $memory->meta ?? [],
                        ])
                        ->all();
                } catch (\Throwable $exception) {
                    Log::warning('agent.memory.recall_failed', [
                        'account_id' => $accountId,
                        'message' => $exception->getMessage(),
                    ]);
                }
            }
        }

        return $results;
    }

    private function resolveApiKey(string $accountId): ?string
    {
        $credential = OpenRouterCredential::query()
            ->where('account_id', $accountId)
            ->first();

        if ($credential === null || $credential->validated_at === null) {
            return null;
        }

        return $credential->api_key;
    }
}
