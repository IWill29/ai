<?php

declare(strict_types=1);

namespace Tests\Feature\AI;

use App\Domains\AI\Services\OpenRouterEmbeddingAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\AgentTestData;
use Tests\TestCase;

class OpenRouterEmbeddingAdapterTest extends TestCase
{
    use RefreshDatabase;

    public function test_uses_merchant_byok_key_on_embeddings_request(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response([
                'data' => [
                    [
                        'embedding' => [0.1, 0.2, 0.3],
                    ],
                ],
            ]),
        ]);

        $embedding = app(OpenRouterEmbeddingAdapter::class)->embed(
            apiKey: 'sk-or-v1-merchant-specific-key',
            text: AgentTestData::MEMORY_PREFERENCE_BRIEF_ANSWERS,
            accountId: 'account-123',
        );

        $this->assertSame([0.1, 0.2, 0.3], $embedding);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer sk-or-v1-merchant-specific-key')
                && str_ends_with($request->url(), '/embeddings')
                && $request['model'] === config('openrouter.embedding_model')
                && $request['dimensions'] === config('openrouter.embedding_dimensions')
                && $request['user'] === 'account-123';
        });
    }
}
