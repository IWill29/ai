<?php

declare(strict_types=1);

namespace Tests\Feature\AI;

use App\Domains\AI\Contracts\AgentLlmPort;
use App\Domains\AI\Contracts\AgentService;
use App\Domains\AI\DTOs\LlmResponse;
use App\Domains\AI\DTOs\ToolCall;
use App\Domains\AI\Services\DefaultAgentService;
use App\Domains\Billing\Models\UsageCounter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAgentFixtures;
use Tests\Concerns\SeedsPlans;
use Tests\TestCase;

class AgentBillingTest extends TestCase
{
    use CreatesAgentFixtures;
    use RefreshDatabase;
    use SeedsPlans;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPlans();
        $this->app->forgetInstance(AgentLlmPort::class);
        $this->app->forgetInstance(AgentService::class);
        $this->app->forgetInstance(DefaultAgentService::class);
    }

    public function test_does_not_increment_counter_when_paused_for_confirmation(): void
    {
        $user = User::factory()->create();
        $store = $this->createStoreForUser($user);
        $this->createOpenRouterCredential($user);
        $conversation = $this->createConversation($user, $store);

        $this->mock(AgentLlmPort::class, function ($mock): void {
            $mock->shouldReceive('stream')
                ->once()
                ->andReturn(new LlmResponse(
                    content: null,
                    toolCalls: [
                        new ToolCall(
                            id: 'call_fulfill_1',
                            name: 'fulfill_order',
                            arguments: ['external_id' => 'gid://shopify/Order/1'],
                        ),
                    ],
                    finishReason: 'tool_calls',
                    promptTokens: 10,
                    completionTokens: 2,
                    model: 'openai/gpt-4o-mini',
                ));
        });

        $response = $this->actingAs($user)->post(route('conversations.stream', $conversation), [
            'message' => 'Fulfill order 1',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('awaiting_confirmation', $response->streamedContent());

        $counter = UsageCounter::query()->where('account_id', $user->account_id)->first();
        $this->assertNotNull($counter);
        $this->assertSame(0, $counter->agent_messages);
    }
}
