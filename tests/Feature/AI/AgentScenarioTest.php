<?php

declare(strict_types=1);

namespace Tests\Feature\AI;

use App\Domains\AI\Contracts\AgentLlmPort;
use App\Domains\AI\Contracts\AgentService;
use App\Domains\AI\DTOs\LlmResponse;
use App\Domains\AI\DTOs\ToolCall;
use App\Domains\AI\Enums\StepStatus;
use App\Domains\AI\Services\DefaultAgentService;
use App\Domains\Billing\Models\AuditLog;
use App\Domains\Billing\Models\UsageCounter;
use App\Domains\Chat\Models\ActionStep;
use App\Domains\Stores\Contracts\StoreAdapterFactory;
use App\Domains\Stores\Contracts\StorePort;
use App\Domains\Stores\DTOs\OrderDTO;
use App\Domains\Stores\Models\SyncedOrder;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Concerns\CreatesAgentFixtures;
use Tests\Concerns\SeedsPlans;
use Tests\TestCase;

/**
 * End-to-end scenario: read from mirror → write pause → confirm → resume → audit → metering.
 *
 * @see docs/planning/phase-8-test-scenario.md
 */
class AgentScenarioTest extends TestCase
{
    use CreatesAgentFixtures;
    use RefreshDatabase;
    use SeedsPlans;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPlans();
        $this->resetAgentContainer();
    }

    public function test_full_agent_happy_path_scenario(): void
    {
        // --- Setup (prerequisites) ---
        $user = User::factory()->create();
        $store = $this->createStoreForUser($user);
        $this->createOpenRouterCredential($user);

        SyncedOrder::query()->create([
            'store_connection_id' => $store->id,
            'external_id' => 'gid://shopify/Order/100',
            'order_number' => '#1001',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'total_price_minor' => 2500,
            'currency' => 'EUR',
            'placed_at' => now(),
        ]);

        // --- A1: model allow-list ---
        $this->actingAs($user)
            ->getJson(route('agent.models'))
            ->assertOk()
            ->assertJsonStructure(['tiers' => [['tier', 'models']]])
            ->assertJsonPath('tiers.0.tier', 'Budget');

        // --- A2: create conversation ---
        $conversationResponse = $this->actingAs($user)->postJson(route('conversations.store'), [
            'store_connection_id' => $store->id,
            'model' => 'openai/gpt-4o-mini',
        ]);

        $conversationResponse->assertOk();
        $conversationId = $conversationResponse->json('conversation.id');
        $this->assertNotEmpty($conversationId);

        // --- A3: read flow (list_orders → answer) ---
        $this->mock(AgentLlmPort::class, function ($mock): void {
            $call = 0;
            $mock->shouldReceive('stream')
                ->twice()
                ->andReturnUsing(function ($apiKey, $model, $messages, $tools, $onDelta) use (&$call) {
                    $call++;

                    if ($call === 1) {
                        return new LlmResponse(
                            content: null,
                            toolCalls: [
                                new ToolCall(
                                    id: 'call_list_1',
                                    name: 'list_orders',
                                    arguments: ['fulfillment_status' => 'unfulfilled', 'limit' => 10],
                                ),
                            ],
                            finishReason: 'tool_calls',
                            promptTokens: 20,
                            completionTokens: 5,
                            model: $model,
                        );
                    }

                    $onDelta('You have 1 unfulfilled order.');

                    return new LlmResponse(
                        content: 'You have 1 unfulfilled order.',
                        toolCalls: [],
                        finishReason: 'stop',
                        promptTokens: 30,
                        completionTokens: 10,
                        model: $model,
                    );
                });
        });

        $readStream = $this->actingAs($user)->post(route('conversations.stream', $conversationId), [
            'message' => 'List unfulfilled orders',
        ]);

        $readBody = $readStream->streamedContent();
        $readStream->assertOk();
        $this->assertStringContainsString('event: step_started', $readBody);
        $this->assertStringContainsString('list_orders', $readBody);
        $this->assertStringContainsString('event: step_done', $readBody);
        $this->assertStringContainsString('event: text_delta', $readBody);
        $this->assertStringContainsString('"status":"completed"', $readBody);

        $this->assertDatabaseHas('action_steps', [
            'tool_name' => 'list_orders',
            'status' => StepStatus::Done->value,
        ]);

        $counter = UsageCounter::query()->where('account_id', $user->account_id)->first();
        $this->assertNotNull($counter);
        $this->assertSame(1, $counter->agent_messages);

        // --- B2: write flow pauses for confirmation ---
        $this->resetAgentContainer();

        $this->mock(AgentLlmPort::class, function ($mock): void {
            $mock->shouldReceive('stream')
                ->once()
                ->andReturn(new LlmResponse(
                    content: null,
                    toolCalls: [
                        new ToolCall(
                            id: 'call_fulfill_1',
                            name: 'fulfill_order',
                            arguments: ['external_id' => 'gid://shopify/Order/100'],
                        ),
                    ],
                    finishReason: 'tool_calls',
                    promptTokens: 15,
                    completionTokens: 3,
                    model: 'openai/gpt-4o-mini',
                ));
        });

        $writeStream = $this->actingAs($user)->post(route('conversations.stream', $conversationId), [
            'message' => 'Fulfill order 100',
        ]);

        $writeBody = $writeStream->streamedContent();
        $writeStream->assertOk();
        $this->assertStringContainsString('event: confirmation_required', $writeBody);
        $this->assertStringContainsString('"status":"awaiting_confirmation"', $writeBody);

        $step = ActionStep::query()
            ->where('tool_name', 'fulfill_order')
            ->where('status', StepStatus::AwaitingConfirmation->value)
            ->firstOrFail();

        $messagesAfterPause = UsageCounter::query()
            ->where('account_id', $user->account_id)
            ->value('agent_messages');
        $this->assertSame(1, $messagesAfterPause);

        // --- B4: confirm write ---
        $orderDto = new OrderDTO(
            externalId: 'gid://shopify/Order/100',
            orderNumber: '#1001',
            financialStatus: 'paid',
            fulfillmentStatus: 'fulfilled',
            totalPriceMinor: 2500,
            currency: 'EUR',
            customerExternalId: null,
            lineItems: [],
            placedAt: new DateTimeImmutable,
        );

        $storePort = Mockery::mock(StorePort::class);
        $storePort->shouldReceive('fulfillOrder')
            ->once()
            ->with('gid://shopify/Order/100', null)
            ->andReturn($orderDto);

        $factory = Mockery::mock(StoreAdapterFactory::class);
        $factory->shouldReceive('for')->andReturn($storePort);
        $this->instance(StoreAdapterFactory::class, $factory);

        $this->actingAs($user)->postJson(route('action-steps.confirm', $step), [
            'confirmed' => true,
        ])->assertOk();

        $step->refresh();
        $this->assertSame(StepStatus::Done->value, $step->status);

        $this->assertDatabaseHas('audit_logs', [
            'account_id' => $user->account_id,
            'action' => 'tool.fulfill_order',
        ]);

        // --- B5: resume stream completes turn ---
        $this->resetAgentContainer();

        $this->mock(AgentLlmPort::class, function ($mock): void {
            $mock->shouldReceive('stream')
                ->once()
                ->andReturnUsing(function ($apiKey, $model, $messages, $tools, $onDelta) {
                    $onDelta('Order fulfilled successfully.');

                    return new LlmResponse(
                        content: 'Order fulfilled successfully.',
                        toolCalls: [],
                        finishReason: 'stop',
                        promptTokens: 25,
                        completionTokens: 8,
                        model: $model,
                    );
                });
        });

        $resumeStream = $this->actingAs($user)->post(route('conversations.stream.resume', $conversationId));

        $resumeBody = $resumeStream->streamedContent();
        $resumeStream->assertOk();
        $this->assertStringContainsString('event: text_delta', $resumeBody);
        $this->assertStringContainsString('"status":"completed"', $resumeBody);

        $counter->refresh();
        $this->assertSame(2, $counter->agent_messages);
    }

    public function test_declined_write_scenario_has_no_audit_log(): void
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
                            id: 'call_fulfill_decline',
                            name: 'fulfill_order',
                            arguments: ['external_id' => 'gid://shopify/Order/200'],
                        ),
                    ],
                    finishReason: 'tool_calls',
                    promptTokens: 10,
                    completionTokens: 2,
                    model: 'openai/gpt-4o-mini',
                ));
        });

        $declineStream = $this->actingAs($user)->post(route('conversations.stream', $conversation), [
            'message' => 'Fulfill order 200',
        ]);

        $declineStream->assertOk();
        $this->assertStringContainsString('confirmation_required', $declineStream->streamedContent());

        $step = ActionStep::query()->where('tool_name', 'fulfill_order')->firstOrFail();

        $storePort = Mockery::mock(StorePort::class);
        $storePort->shouldNotReceive('fulfillOrder');
        $factory = Mockery::mock(StoreAdapterFactory::class);
        $factory->shouldReceive('for')->andReturn($storePort);
        $this->instance(StoreAdapterFactory::class, $factory);

        $this->actingAs($user)->postJson(route('action-steps.confirm', $step), [
            'confirmed' => false,
        ])->assertOk();

        $step->refresh();
        $this->assertSame(StepStatus::Failed->value, $step->status);
        $this->assertFalse($step->confirmed);
        $this->assertSame(0, AuditLog::query()->count());
    }

    private function resetAgentContainer(): void
    {
        $this->app->forgetInstance(AgentLlmPort::class);
        $this->app->forgetInstance(AgentService::class);
        $this->app->forgetInstance(DefaultAgentService::class);
    }
}
