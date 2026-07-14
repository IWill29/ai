<?php

declare(strict_types=1);

namespace Tests\Feature\AI;

use App\Domains\AI\DTOs\LlmMessage;
use App\Domains\AI\Exceptions\LlmUnavailableException;
use App\Domains\AI\Services\OpenRouterAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenRouterAdapterFailureTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_openrouter_response_does_not_leak_response_body(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response(
                ['error' => ['message' => 'secret prompt', 'api_key' => 'sk-or-v1-leaked']],
                502,
            ),
        ]);

        try {
            app(OpenRouterAdapter::class)->chat(
                apiKey: 'sk-or-v1-merchant-specific-key',
                model: 'openai/gpt-4o-mini',
                messages: [new LlmMessage(role: 'user', content: 'Hello')],
            );

            $this->fail('Expected LlmUnavailableException was not thrown.');
        } catch (LlmUnavailableException $exception) {
            $message = $exception->getMessage();

            $this->assertStringContainsString('HTTP 502', $message);
            $this->assertStringNotContainsString('secret prompt', $message);
            $this->assertStringNotContainsString('sk-or-v1-leaked', $message);
        }
    }
}
