<?php

declare(strict_types=1);

namespace Tests\Feature\AI;

use App\Domains\AI\DTOs\LlmMessage;
use App\Domains\AI\Services\OpenRouterAdapter;
use App\Domains\AI\Support\OpenRouterModels;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenRouterAdapterUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_uses_merchant_byok_key_on_chat_request(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response([
                'model' => OpenRouterModels::GPT_4O_MINI,
                'choices' => [
                    [
                        'message' => ['role' => 'assistant', 'content' => 'OK'],
                        'finish_reason' => 'stop',
                    ],
                ],
                'usage' => ['prompt_tokens' => 1, 'completion_tokens' => 1],
            ]),
        ]);

        $response = app(OpenRouterAdapter::class)->chat(
            apiKey: 'sk-or-v1-merchant-specific-key',
            model: OpenRouterModels::GPT_4O_MINI,
            messages: [new LlmMessage(role: 'user', content: 'Hello')],
        );

        $this->assertSame('OK', $response->content);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer sk-or-v1-merchant-specific-key');
        });
    }
}
