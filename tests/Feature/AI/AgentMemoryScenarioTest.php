<?php

declare(strict_types=1);

namespace Tests\Feature\AI;

use App\Domains\AI\Contracts\AgentLlmPort;
use App\Domains\AI\Contracts\AgentService;
use App\Domains\AI\Contracts\MemoryService;
use App\Domains\AI\DTOs\LlmResponse;
use App\Domains\AI\DTOs\ToolCall;
use App\Domains\AI\Enums\MemorySource;
use App\Domains\AI\Enums\StepStatus;
use App\Domains\AI\Models\AgentMemory;
use App\Domains\AI\Services\AgentMemoryRecorder;
use App\Domains\AI\Services\AgentMessageHistoryBuilder;
use App\Domains\AI\Services\DefaultAgentService;
use App\Domains\AI\Services\VectorMemoryService;
use App\Domains\Billing\Models\AuditLog;
use App\Domains\Chat\Contracts\ChatService;
use App\Domains\Chat\Models\ActionStep;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Stores\Contracts\StoreAdapterFactory;
use App\Domains\Stores\Contracts\StorePort;
use App\Domains\Stores\DTOs\OrderDTO;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Concerns\BindsDeterministicAgentMemory;
use Tests\Concerns\CreatesAgentFixtures;
use Tests\Concerns\SeedsPlans;
use Tests\Support\AgentTestData;
use Tests\TestCase;

/**
 * End-to-end memory scenarios: preference capture → recall → confirmed action memory.
 *
 * @see docs/planning/phase-9-test-scenario.md
 */
class AgentMemoryScenarioTest extends TestCase
{
    use BindsDeterministicAgentMemory;
    use CreatesAgentFixtures;
    use RefreshDatabase;
    use SeedsPlans;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPlans();
        $this->bindDeterministicMemory();
        $this->resetAgentContainer();
    }

    public function test_agent_memory_recorder_stores_preference_directly(): void
    {
        $user = User::factory()->create();
        $this->createOpenRouterCredential($user);

        app(AgentMemoryRecorder::class)->recordPreferenceIfPresent(
            $user->account_id,
            AgentTestData::MERCHANT_MESSAGE_REMEMBER_BRIEF,
        );

        $this->assertDatabaseHas('agent_memories', [
            'account_id' => $user->account_id,
            'content' => AgentTestData::MEMORY_PREFERENCE_WANT_BRIEF,
        ]);
    }

    public function test_merchant_preference_is_stored_on_agent_run(): void
    {
        $user = User::factory()->create();
        $store = $this->createStoreForUser($user);
        $this->createOpenRouterCredential($user);
        $conversation = $this->createConversation($user, $store);

        $this->resetAgentContainer();
        $this->mockSimpleAnswerLlm('Got it.');

        $stream = $this->actingAs($user)->post(route('conversations.stream', $conversation), [
            'message' => AgentTestData::MERCHANT_MESSAGE_REMEMBER_BRIEF,
        ]);

        $stream->assertOk();
        $this->assertStringContainsString(AgentTestData::SSE_STATUS_COMPLETED, $stream->streamedContent());

        $this->assertDatabaseHas('agent_memories', [
            'account_id' => $user->account_id,
            'content' => AgentTestData::MEMORY_PREFERENCE_WANT_BRIEF,
        ]);

        $memory = AgentMemory::query()->where('account_id', $user->account_id)->firstOrFail();
        $this->assertSame(MemorySource::MerchantPreference->value, $memory->meta['source']);
    }

    public function test_preference_memory_is_injected_into_system_prompt_in_new_conversation(): void
    {
        $user = User::factory()->create();
        $store = $this->createStoreForUser($user);
        $this->createOpenRouterCredential($user);

        app(MemoryService::class)->remember(
            $user->account_id,
            AgentTestData::MEMORY_PREFERENCE_KEEP_BRIEF,
            ['source' => MemorySource::MerchantPreference->value],
        );

        $firstConversation = $this->createConversation($user, $store, ['title' => 'Thread A']);
        $secondConversation = $this->createConversation($user, $store, ['title' => 'Thread B']);

        $chat = app(ChatService::class);
        $userMessage = $chat->appendUserMessage(
            $secondConversation->id,
            'Give me a brief summary of yesterday orders',
        );

        $messages = app(AgentMessageHistoryBuilder::class)->build(
            Conversation::query()->with('storeConnection')->findOrFail($secondConversation->id),
            $userMessage,
        );

        $this->assertStringContainsString('Relevant memories:', $messages[0]->content ?? '');
        $this->assertStringContainsString(AgentTestData::MEMORY_PREFERENCE_KEEP_BRIEF, $messages[0]->content ?? '');
        $this->assertNotSame($firstConversation->id, $secondConversation->id);
    }

    public function test_confirmed_write_action_is_stored_as_memory(): void
    {
        $user = User::factory()->create();
        $store = $this->createStoreForUser($user);
        $this->createOpenRouterCredential($user);
        $conversation = $this->createConversation($user, $store);

        $this->resetAgentContainer();
        $this->mockFulfillOrderLlm(AgentTestData::ORDER_100);

        $stream = $this->actingAs($user)->post(route('conversations.stream', $conversation), [
            'message' => AgentTestData::CHAT_FULFILL_ORDER_100,
        ]);

        $stream->assertOk();
        $streamBody = $stream->streamedContent();
        $this->assertStringContainsString('confirmation_required', $streamBody, $streamBody);

        $step = ActionStep::query()
            ->where('tool_name', 'fulfill_order')
            ->where('status', StepStatus::AwaitingConfirmation->value)
            ->firstOrFail();

        $this->confirmFulfillment($user, $step, AgentTestData::ORDER_100);

        $this->assertDatabaseHas('agent_memories', [
            'account_id' => $user->account_id,
        ]);

        $memory = AgentMemory::query()->where('account_id', $user->account_id)->firstOrFail();
        $this->assertSame(MemorySource::ConfirmedAction->value, $memory->meta['source']);
        $this->assertSame('fulfill_order', $memory->meta['tool']);
        $this->assertStringContainsString(AgentTestData::MEMORY_FULFILL_PHRASE, $memory->content);
        $this->assertStringContainsString(AgentTestData::ORDER_100, $memory->content);
    }

    public function test_declined_write_does_not_store_memory(): void
    {
        $user = User::factory()->create();
        $store = $this->createStoreForUser($user);
        $this->createOpenRouterCredential($user);
        $conversation = $this->createConversation($user, $store);

        $this->resetAgentContainer();
        $this->mockFulfillOrderLlm(AgentTestData::ORDER_200);

        $stream = $this->actingAs($user)->post(route('conversations.stream', $conversation), [
            'message' => AgentTestData::CHAT_FULFILL_ORDER_200,
        ]);

        $stream->assertOk();
        $this->assertStringContainsString('confirmation_required', $stream->streamedContent());

        $step = ActionStep::query()->where('tool_name', 'fulfill_order')->firstOrFail();

        $storePort = Mockery::mock(StorePort::class);
        $storePort->shouldNotReceive('fulfillOrder');
        $factory = Mockery::mock(StoreAdapterFactory::class);
        $factory->shouldReceive('for')->andReturn($storePort);
        $this->instance(StoreAdapterFactory::class, $factory);

        $this->actingAs($user)->postJson(route('action-steps.confirm', $step), [
            'confirmed' => false,
        ])->assertOk();

        $this->assertSame(0, AgentMemory::query()->where('account_id', $user->account_id)->count());
        $this->assertSame(0, AuditLog::query()->count());
    }

    public function test_memory_skips_gracefully_without_api_key(): void
    {
        $user = User::factory()->create();

        $memory = app(MemoryService::class);
        $memory->remember($user->account_id, AgentTestData::MEMORY_PREFERENCE_BRIEF_ANSWERS);
        $recalled = $memory->recall($user->account_id, 'brief summary');

        $this->assertSame([], $recalled);
        $this->assertSame(0, AgentMemory::query()->where('account_id', $user->account_id)->count());
    }

    public function test_operational_message_does_not_store_preference_memory(): void
    {
        $user = User::factory()->create();
        $store = $this->createStoreForUser($user);
        $this->createOpenRouterCredential($user);
        $conversation = $this->createConversation($user, $store);

        $this->resetAgentContainer();
        $this->mockSimpleAnswerLlm('You have 3 unfulfilled orders.');

        $stream = $this->actingAs($user)->post(route('conversations.stream', $conversation), [
            'message' => 'List unfulfilled orders',
        ]);

        $stream->assertOk();
        $this->assertStringContainsString(AgentTestData::SSE_STATUS_COMPLETED, $stream->streamedContent());
        $this->assertSame(0, AgentMemory::query()->where('account_id', $user->account_id)->count());
    }

    public function test_system_prompt_omits_memory_block_when_no_memories_exist(): void
    {
        $user = User::factory()->create();
        $store = $this->createStoreForUser($user);
        $this->createOpenRouterCredential($user);
        $conversation = $this->createConversation($user, $store);

        $chat = app(ChatService::class);
        $userMessage = $chat->appendUserMessage($conversation->id, 'Show recent orders');

        $messages = app(AgentMessageHistoryBuilder::class)->build(
            Conversation::query()->with('storeConnection')->findOrFail($conversation->id),
            $userMessage,
        );

        $this->assertStringNotContainsString('Relevant memories:', $messages[0]->content ?? '');
    }

    public function test_multiple_merchant_preferences_accumulate(): void
    {
        $user = User::factory()->create();
        $this->createOpenRouterCredential($user);
        $recorder = app(AgentMemoryRecorder::class);

        $recorder->recordPreferenceIfPresent($user->account_id, AgentTestData::MERCHANT_MESSAGE_REMEMBER_BRIEF);
        $recorder->recordPreferenceIfPresent($user->account_id, 'Always use EUR formatting');

        $this->assertSame(2, AgentMemory::query()->where('account_id', $user->account_id)->count());

        $recalled = app(MemoryService::class)->recall($user->account_id, 'brief EUR summary', 5);

        $this->assertGreaterThanOrEqual(1, count($recalled));
        $contents = array_column($recalled, 'content');
        $this->assertTrue(
            in_array(AgentTestData::MEMORY_PREFERENCE_WANT_BRIEF, $contents, true)
            || in_array('Merchant preference: use EUR formatting', $contents, true),
            'Expected at least one stored preference in recall results.',
        );
    }

    public function test_account_deletion_cascades_agent_memories(): void
    {
        $user = User::factory()->create();
        $this->createOpenRouterCredential($user);

        app(MemoryService::class)->remember(
            $user->account_id,
            AgentTestData::MEMORY_PREFERENCE_KEEP_BRIEF,
            ['source' => MemorySource::MerchantPreference->value],
        );

        $this->assertSame(1, AgentMemory::query()->where('account_id', $user->account_id)->count());

        $this->actingAs($user)
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertRedirect(route('home'));

        $this->assertSame(0, AgentMemory::query()->count());
    }

    public function test_confirmed_action_and_preference_both_recalled_in_new_conversation(): void
    {
        $user = User::factory()->create();
        $this->createOpenRouterCredential($user);

        $memory = app(MemoryService::class);
        $memory->remember(
            $user->account_id,
            AgentTestData::MEMORY_PREFERENCE_KEEP_BRIEF,
            ['source' => MemorySource::MerchantPreference->value],
        );
        $memory->remember(
            $user->account_id,
            AgentTestData::MEMORY_CONFIRMED_FULFILL_ORDER_100,
            ['source' => MemorySource::ConfirmedAction->value],
        );

        $recalled = $memory->recall($user->account_id, 'Give me a brief fulfill summary', 5);

        $this->assertCount(2, $recalled);

        $contents = array_column($recalled, 'content');
        $this->assertContains(AgentTestData::MEMORY_PREFERENCE_KEEP_BRIEF, $contents);
        $this->assertTrue(
            collect($contents)->contains(fn (string $content): bool => str_contains($content, AgentTestData::MEMORY_FULFILL_PHRASE)),
        );
    }

    public function test_confirmed_action_memory_persists_after_resume(): void
    {
        $user = User::factory()->create();
        $store = $this->createStoreForUser($user);
        $this->createOpenRouterCredential($user);
        $conversation = $this->createConversation($user, $store);

        $this->resetAgentContainer();
        $this->mockFulfillOrderLlm(AgentTestData::ORDER_100);

        $writeStream = $this->actingAs($user)->post(route('conversations.stream', $conversation), [
            'message' => AgentTestData::CHAT_FULFILL_ORDER_100,
        ]);
        $writeStream->assertOk();
        $this->assertStringContainsString('confirmation_required', $writeStream->streamedContent());

        $step = ActionStep::query()
            ->where('tool_name', 'fulfill_order')
            ->where('status', StepStatus::AwaitingConfirmation->value)
            ->firstOrFail();

        $this->confirmFulfillment($user, $step, AgentTestData::ORDER_100);
        $this->assertSame(1, AgentMemory::query()->where('account_id', $user->account_id)->count());

        $this->resetAgentContainer();
        $this->mock(AgentLlmPort::class, function ($mock): void {
            $mock->shouldReceive('stream')
                ->once()
                ->andReturnUsing(function (...$args) {
                    $onDelta = $args[4];
                    $onDelta('Done.');

                    return new LlmResponse(
                        content: 'Done.',
                        toolCalls: [],
                        finishReason: 'stop',
                        promptTokens: 10,
                        completionTokens: 3,
                        model: $args[1],
                    );
                });
        });

        $resumeStream = $this->actingAs($user)->post(route('conversations.stream.resume', $conversation));
        $resumeStream->assertOk();
        $this->assertStringContainsString(AgentTestData::SSE_STATUS_COMPLETED, $resumeStream->streamedContent());

        $memory = AgentMemory::query()->where('account_id', $user->account_id)->firstOrFail();
        $this->assertSame(MemorySource::ConfirmedAction->value, $memory->meta['source']);

        $recalled = app(MemoryService::class)->recall($user->account_id, 'fulfill order 100 again');
        $this->assertNotEmpty($recalled);
        $this->assertStringContainsString(AgentTestData::MEMORY_FULFILL_PHRASE, $recalled[0]['content']);
    }

    private function resetAgentContainer(): void
    {
        foreach ([
            AgentLlmPort::class,
            AgentService::class,
            DefaultAgentService::class,
            AgentMemoryRecorder::class,
            MemoryService::class,
            VectorMemoryService::class,
            AgentMessageHistoryBuilder::class,
        ] as $abstract) {
            $this->app->forgetInstance($abstract);
        }
    }

    private function mockSimpleAnswerLlm(string $answer): void
    {
        $this->mock(AgentLlmPort::class, function ($mock) use ($answer): void {
            $mock->shouldReceive('stream')
                ->once()
                ->andReturnUsing(function (...$args) use ($answer) {
                    $model = $args[1];
                    $onDelta = $args[4];
                    $onDelta($answer);

                    return new LlmResponse(
                        content: $answer,
                        toolCalls: [],
                        finishReason: 'stop',
                        promptTokens: 10,
                        completionTokens: 5,
                        model: $model,
                    );
                });
        });
    }

    private function mockFulfillOrderLlm(string $orderExternalId): void
    {
        $this->mock(AgentLlmPort::class, function ($mock) use ($orderExternalId): void {
            $mock->shouldReceive('stream')
                ->once()
                ->andReturn(new LlmResponse(
                    content: null,
                    toolCalls: [
                        new ToolCall(
                            id: 'call_fulfill_memory',
                            name: 'fulfill_order',
                            arguments: ['external_id' => $orderExternalId],
                        ),
                    ],
                    finishReason: 'tool_calls',
                    promptTokens: 15,
                    completionTokens: 3,
                    model: AgentTestData::DEFAULT_MODEL,
                ));
        });
    }

    private function confirmFulfillment(User $user, ActionStep $step, string $orderExternalId): void
    {
        $orderDto = new OrderDTO(
            externalId: $orderExternalId,
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
            ->with($orderExternalId, null)
            ->andReturn($orderDto);

        $factory = Mockery::mock(StoreAdapterFactory::class);
        $factory->shouldReceive('for')->andReturn($storePort);
        $this->instance(StoreAdapterFactory::class, $factory);

        $this->actingAs($user)->postJson(route('action-steps.confirm', $step), [
            'confirmed' => true,
        ])->assertOk();

        $step->refresh();
        $this->assertSame(StepStatus::Done->value, $step->status);
    }
}
