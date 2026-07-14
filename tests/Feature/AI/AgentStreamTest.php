<?php

declare(strict_types=1);

namespace Tests\Feature\AI;

use App\Domains\AI\Contracts\AgentLlmPort;
use App\Domains\AI\DTOs\LlmResponse;
use App\Domains\AI\Enums\StepStatus;
use App\Domains\Billing\Models\UsageCounter;
use App\Domains\Chat\Models\ActionStep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAgentFixtures;
use Tests\Concerns\MocksAgentLlm;
use Tests\Concerns\SeedsPlans;
use Tests\Support\AgentTestData;
use Tests\TestCase;

class AgentStreamTest extends TestCase
{
    use CreatesAgentFixtures;
    use MocksAgentLlm;
    use RefreshDatabase;
    use SeedsPlans;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPlans();
        $this->resetAgentContainer();
    }

    public function test_streams_text_deltas_via_sse(): void
    {
        $user = User::factory()->create();
        $store = $this->createStoreForUser($user);
        $this->createOpenRouterCredential($user);
        $conversation = $this->createConversation($user, $store);

        $this->mock(AgentLlmPort::class, function ($mock): void {
            $mock->shouldReceive('stream')
                ->once()
                ->andReturnUsing(function (...$args) {
                    $model = $args[1];
                    $onDelta = $args[4];
                    $onDelta('Hello');

                    return new LlmResponse(
                        content: 'Hello',
                        toolCalls: [],
                        finishReason: 'stop',
                        promptTokens: 10,
                        completionTokens: 2,
                        model: $model,
                    );
                });
        });

        $response = $this->actingAs($user)->post(route('conversations.stream', $conversation), [
            'message' => 'Hi',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('event: text_delta', $response->streamedContent());
        $this->assertStringContainsString('event: done', $response->streamedContent());
    }

    public function test_pauses_on_write_tool_for_confirmation(): void
    {
        $user = User::factory()->create();
        $store = $this->createStoreForUser($user);
        $this->createOpenRouterCredential($user);
        $conversation = $this->createConversation($user, $store);

        $this->mockFulfillOrderLlm();

        $response = $this->actingAs($user)->post(route('conversations.stream', $conversation), [
            'message' => AgentTestData::CHAT_FULFILL_ORDER_1,
        ]);

        $this->assertStreamRequiresConfirmation($response);

        $step = ActionStep::query()->first();
        $this->assertNotNull($step);
        $this->assertSame(StepStatus::AwaitingConfirmation->value, $step->status);
    }

    public function test_executes_write_after_confirmation(): void
    {
        $user = User::factory()->create();
        $store = $this->createStoreForUser($user);
        $this->createOpenRouterCredential($user);
        $conversation = $this->createConversation($user, $store);

        $this->mockFulfillOrderLlm();

        $response = $this->actingAs($user)->post(route('conversations.stream', $conversation), [
            'message' => AgentTestData::CHAT_FULFILL_ORDER_1,
        ]);

        $this->assertStreamRequiresConfirmation($response);

        $step = ActionStep::query()->firstOrFail();
        $this->confirmFulfillment($user, $step, AgentTestData::ORDER_1, orderNumber: '#1');

        $step->refresh();
        $this->assertTrue($step->confirmed);
    }

    public function test_increments_counter_only_on_completed_turn(): void
    {
        $user = User::factory()->create();
        $store = $this->createStoreForUser($user);
        $this->createOpenRouterCredential($user);
        $conversation = $this->createConversation($user, $store);

        $this->mock(AgentLlmPort::class, function ($mock): void {
            $mock->shouldReceive('stream')
                ->once()
                ->andReturn(new LlmResponse(
                    content: 'Done',
                    toolCalls: [],
                    finishReason: 'stop',
                    promptTokens: 10,
                    completionTokens: 2,
                    model: AgentTestData::DEFAULT_MODEL,
                ));
        });

        $response = $this->actingAs($user)->post(route('conversations.stream', $conversation), [
            'message' => 'Summarize orders',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('event: done', $response->streamedContent(), $response->streamedContent());

        $counter = UsageCounter::query()->where('account_id', $user->account_id)->first();
        $this->assertNotNull($counter);
        $this->assertSame(1, $counter->agent_messages);
    }

    public function test_enforces_message_limit(): void
    {
        $user = User::factory()->create();
        $store = $this->createStoreForUser($user);
        $this->createOpenRouterCredential($user);
        $conversation = $this->createConversation($user, $store);

        UsageCounter::query()->create([
            'account_id' => $user->account_id,
            'period' => now()->format('Y-m'),
            'agent_messages' => 100,
        ]);

        $response = $this->actingAs($user)->post(route('conversations.stream', $conversation), [
            'message' => 'Hi',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('event: error', $response->streamedContent());
    }

    public function test_rejects_disallowed_model(): void
    {
        $user = User::factory()->create();
        $store = $this->createStoreForUser($user);
        $this->createOpenRouterCredential($user);
        $conversation = $this->createConversation($user, $store);

        $response = $this->actingAs($user)->post(route('conversations.stream', $conversation), [
            'message' => 'Hi',
            'model' => 'gpt-3.5-turbo',
        ]);

        $response->assertStatus(422);
    }
}
