<?php

declare(strict_types=1);

namespace Tests\Feature\Stores;

use App\Domains\Stores\Enums\Platform;
use App\Domains\Stores\Models\StoreConnection;
use App\Domains\Stores\Models\StoreCredential;
use App\Domains\Stores\Models\SyncedOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsPlans;
use Tests\TestCase;

class DisconnectStoreTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPlans;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPlans();
    }

    public function test_disconnect_purges_credentials_and_synced_data(): void
    {
        $user = User::factory()->create();

        $connection = StoreConnection::query()->create([
            'account_id' => $user->account_id,
            'platform' => Platform::Shopify->value,
            'name' => 'Demo',
            'domain' => 'demo.myshopify.com',
            'status' => 'active',
        ]);

        StoreCredential::query()->create([
            'store_connection_id' => $connection->id,
            'access_token' => 'shpat_test_token_1234567890',
        ]);

        SyncedOrder::query()->create([
            'store_connection_id' => $connection->id,
            'external_id' => 'gid://shopify/Order/1',
            'order_number' => '#1001',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'total_price_minor' => 1230,
            'currency' => 'EUR',
            'placed_at' => now(),
            'raw' => [],
        ]);

        $this->actingAs($user)
            ->delete(route('stores.destroy', $connection))
            ->assertRedirect(route('stores.index'));

        $this->assertDatabaseMissing('store_connections', ['id' => $connection->id]);
        $this->assertDatabaseMissing('store_credentials', ['store_connection_id' => $connection->id]);
        $this->assertDatabaseMissing('synced_orders', ['store_connection_id' => $connection->id]);
    }
}
