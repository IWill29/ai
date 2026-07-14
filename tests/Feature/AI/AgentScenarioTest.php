<?php

declare(strict_types=1);

namespace Tests\Feature\AI;

use App\Domains\AI\Contracts\AgentLlmPort;
use App\Domains\AI\DTOs\LlmResponse;
use App\Domains\AI\DTOs\ToolCall;
use App\Domains\AI\Enums\StepStatus;
use App\Domains\Billing\Models\AuditLog;
use App\Domains\Billing\Models\UsageCounter;
use App\Domains\Chat\Models\ActionStep;
use App\Domains\Stores\Models\SyncedOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\CreatesAgentFixtures;
use Tests\Concerns\MocksAgentLlm;
use Tests\Concerns\SeedsPlans;
use Tests\Support\AgentTestData;
use Tests\TestCase;

/**
 * End-to-end scenario: read from mirror → write pause → confirm → resume → audit → metering.
 *
 * @see docs/planning/phase-8-test-scenario.md
 */
class AgentScenarioTest extends TestCase
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

    public function test_full_agent_happy_path_scenario(): void
    {
        $user = User::factory()->create();
        $store = $this->createStoreForUser($user);
        $this->createOpenRouterCredential($user);
        $this->seedUnfulfilledOrder($store->id);

        $this->assertModelAllowList($user);
        $conversationId = $this->createConversationForStore($user, $store->id);

        $this->mockReadThenAnswerLlm();
        $this->assertReadStreamCompletes($user, $conversationId);

        $counter = UsageCounter::query()->where('account_id', $user->account_id)->first();
        $this->assertNotNull($counter);
        $this->assertSame(1, $counter->agent_messages);

        $this->resetAgentContainer();
        $this->mockFulfillOrderLlm(AgentTestData::ORDER_100);
        $step = $this->assertWriteStreamPauses($user, $conversationId);
        $this->assertSame(1, UsageCounter::query()->where('account_id', $user->account_id)->value('agent_messages'));

        $this->confirmFulfillment($user, $step, AgentTestData::ORDER_100, expectAuditLog: true);

        $this->resetAgentContainer();
        $this->mockResumeAnswerLlm('Order fulfilled successfully.');
        $this->assertResumeStreamCompletes($user, $conversationId);

        $counter->refresh();
        $this->assertSame(2, $counter->agent_messages);
    }

    public function test_declined_write_scenario_has_no_audit_log(): void
    {
        $user = User::factory()->create();
        $store = $this->createStoreForUser($user);
        $this->createOpenRouterCredential($user);
        $conversation = $this->createConversation($user, $store);

        $this->mockFulfillOrderLlm(AgentTestData::ORDER_200);

        $declineStream = $this->actingAs($user)->post(route('conversations.stream', $conversation), [
            'message' => AgentTestData::CHAT_FULFILL_ORDER_200,
        ]);

        $this->assertStreamRequiresConfirmation($declineStream);

        $step = ActionStep::query()->where('tool_name', 'fulfill_order')->firstOrFail();
        $this->declineFulfillment($user, $step);

        $step->refresh();
        $this->assertSame(StepStatus::Failed->value, $step->status);
        $this->assertFalse($step->confirmed);
        $this->assertSame(0, AuditLog::query()->count());
    }

    private function seedUnfulfilledOrder(string $storeId): void
    {
        SyncedOrder::query()->create([
            'store_connection_id' => $storeId,
            'external_id' => AgentTestData::ORDER_100,
            'order_number' => '#1001',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'total_price_minor' => 2500,
            'currency' => 'EUR',
            'placed_at' => now(),
        ]);
    }

    private function assertModelAllowList(User $user): void
    {
        $this->actingAs($user)
            ->getJson(route('agent.models'))
            ->assertOk()
            ->assertJsonStructure(['tiers' => [['tier', 'models']]])
            ->assertJsonPath('tiers.0.tier', 'Budget');
    }

    private function createConversationForStore(User $user, string $storeId): string
    {
        $response = $this->actingAs($user)->postJson(route('conversations.store'), [
            'store_connection_id' => $storeId,
            'model' => AgentTestData::DEFAULT_MODEL,
        ]);

        $response->assertOk();
        $conversationId = $response->json('conversation.id');
        $this->assertNotEmpty($conversationId);

        return $conversationId;
    }

    private function mockReadThenAnswerLlm(): void
    {
        $this->mock(AgentLlmPort::class, function ($mock): void {
            $call = 0;
            $mock->shouldReceive('stream')
                ->twice()
                ->andReturnUsing(function (...$args) use (&$call) {
                    $call++;
                    $model = $args[1];
                    $onDelta = $args[4];

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
    }

    private function assertReadStreamCompletes(User $user, string $conversationId): void
    {
        $readStream = $this->actingAs($user)->post(route('conversations.stream', $conversationId), [
            'message' => 'List unfulfilled orders',
        ]);

        $readBody = $readStream->streamedContent();
        $readStream->assertOk();
        $this->assertStringContainsString('event: step_started', $readBody);
        $this->assertStringContainsString('list_orders', $readBody);
        $this->assertStringContainsString('event: step_done', $readBody);
        $this->assertStringContainsString('event: text_delta', $readBody);
        $this->assertStringContainsString(AgentTestData::SSE_STATUS_COMPLETED, $readBody);

        $this->assertDatabaseHas('action_steps', [
            'tool_name' => 'list_orders',
            'status' => StepStatus::Done->value,
        ]);
    }

    private function assertWriteStreamPauses(User $user, string $conversationId): ActionStep
    {
        $writeStream = $this->actingAs($user)->post(route('conversations.stream', $conversationId), [
            'message' => AgentTestData::CHAT_FULFILL_ORDER_100,
        ]);

        $writeBody = $writeStream->streamedContent();
        $writeStream->assertOk();
        $this->assertStringContainsString(AgentTestData::SSE_EVENT_CONFIRMATION_REQUIRED, $writeBody);
        $this->assertStringContainsString(AgentTestData::SSE_STATUS_AWAITING_CONFIRMATION, $writeBody);

        return ActionStep::query()
            ->where('tool_name', 'fulfill_order')
            ->where('status', StepStatus::AwaitingConfirmation->value)
            ->firstOrFail();
    }

    private function assertResumeStreamCompletes(User $user, string $conversationId): TestResponse
    {
        $resumeStream = $this->actingAs($user)->post(route('conversations.stream.resume', $conversationId));

        $resumeBody = $resumeStream->streamedContent();
        $resumeStream->assertOk();
        $this->assertStringContainsString('event: text_delta', $resumeBody);
        $this->assertStringContainsString(AgentTestData::SSE_STATUS_COMPLETED, $resumeBody);

        return $resumeStream;
    }
}
