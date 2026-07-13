<?php

declare(strict_types=1);

namespace Tests\Feature\AI;

use App\Domains\AI\Contracts\AgentLlmPort;
use App\Domains\AI\Contracts\AgentService;
use App\Domains\AI\DTOs\LlmResponse;
use App\Domains\AI\DTOs\ToolCall;
use App\Domains\AI\Enums\StepStatus;
use App\Domains\AI\Services\DefaultAgentService;
use App\Domains\Billing\Models\UsageCounter;
use App\Domains\Chat\Models\ActionStep;
use App\Domains\Stores\Contracts\StoreAdapterFactory;
use App\Domains\Stores\Contracts\StorePort;
use App\Domains\Stores\DTOs\OrderDTO;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Concerns\CreatesAgentFixtures;
use Tests\Concerns\SeedsPlans;
use Tests\Support\AgentTestData;
use Tests\TestCase;

class AgentStreamTest extends TestCase
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
            'message' => 'Fulfill order 1',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('event: confirmation_required', $response->streamedContent());

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
            'message' => 'Fulfill order 1',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('confirmation_required', $response->streamedContent(), $response->streamedContent());

        $step = ActionStep::query()->firstOrFail();

        $orderDto = new OrderDTO(
            externalId: AgentTestData::ORDER_1,
            orderNumber: '#1',
            financialStatus: 'paid',
            fulfillmentStatus: 'fulfilled',
            totalPriceMinor: 1000,
            currency: 'EUR',
            customerExternalId: null,
            lineItems: [],
            placedAt: new DateTimeImmutable,
        );

        $storePort = Mockery::mock(StorePort::class);
        $storePort->shouldReceive('fulfillOrder')
            ->once()
            ->with(AgentTestData::ORDER_1, null)
            ->andReturn($orderDto);

        $factory = Mockery::mock(StoreAdapterFactory::class);
        $factory->shouldReceive('for')->andReturn($storePort);
        $this->instance(StoreAdapterFactory::class, $factory);

        $this->actingAs($user)->postJson(route('action-steps.confirm', $step), [
            'confirmed' => true,
        ])->assertOk();

        $step->refresh();
        $this->assertSame(StepStatus::Done->value, $step->status);
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

    private function mockFulfillOrderLlm(): void
    {
        $this->mock(AgentLlmPort::class, function ($mock): void {
            $mock->shouldReceive('stream')
                ->once()
                ->andReturn(new LlmResponse(
                    content: null,
                    toolCalls: [
                        new ToolCall(
                            id: 'call_fulfill_1',
                            name: 'fulfill_order',
                            arguments: ['external_id' => AgentTestData::ORDER_1],
                        ),
                    ],
                    finishReason: 'tool_calls',
                    promptTokens: 10,
                    completionTokens: 2,
                    model: AgentTestData::DEFAULT_MODEL,
                ));
        });
    }
}
