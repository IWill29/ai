<?php

declare(strict_types=1);

namespace Tests\Feature\AI;

use App\Domains\AI\Enums\ToolName;
use App\Domains\AI\Tools\ToolExecutor;
use App\Domains\Stores\Contracts\StorePort;
use App\Domains\Stores\Models\SyncedOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Concerns\CreatesAgentFixtures;
use Tests\Concerns\SeedsPlans;
use Tests\Support\AgentTestData;
use Tests\TestCase;

class ToolExecutorMirrorTest extends TestCase
{
    use CreatesAgentFixtures;
    use RefreshDatabase;
    use SeedsPlans;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPlans();
    }

    public function test_list_orders_read_tool_queries_mirror_not_store_port(): void
    {
        $user = User::factory()->create();
        $store = $this->createStoreForUser($user);

        SyncedOrder::query()->create([
            'store_connection_id' => $store->id,
            'external_id' => AgentTestData::ORDER_100,
            'order_number' => '#1001',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'total_price_minor' => 2500,
            'currency' => 'EUR',
            'placed_at' => now(),
        ]);

        $storePort = Mockery::mock(StorePort::class);
        $storePort->shouldNotReceive('listOrders');

        $result = app(ToolExecutor::class)->execute(
            store: $store,
            storePort: null,
            accountId: $user->account_id,
            tool: ToolName::ListOrders,
            args: [],
        );

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $result['data']['count']);
    }
}
