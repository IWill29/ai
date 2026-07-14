<?php

declare(strict_types=1);

namespace Tests\Feature\AI;

use App\Domains\Billing\Models\UsageCounter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAgentFixtures;
use Tests\Concerns\MocksAgentLlm;
use Tests\Concerns\SeedsPlans;
use Tests\Support\AgentTestData;
use Tests\TestCase;

class AgentBillingTest extends TestCase
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

    public function test_does_not_increment_counter_when_paused_for_confirmation(): void
    {
        $user = User::factory()->create();
        $store = $this->createStoreForUser($user);
        $this->createOpenRouterCredential($user);
        $conversation = $this->createConversation($user, $store);

        $this->mockFulfillOrderLlm();

        $response = $this->actingAs($user)->post(route('conversations.stream', $conversation), [
            'message' => AgentTestData::CHAT_FULFILL_ORDER_1,
        ]);

        $response->assertOk();
        $this->assertStringContainsString(AgentTestData::SSE_STATUS_AWAITING_CONFIRMATION, $response->streamedContent());

        $counter = UsageCounter::query()->where('account_id', $user->account_id)->first();
        $this->assertNotNull($counter);
        $this->assertSame(0, $counter->agent_messages);
    }
}
