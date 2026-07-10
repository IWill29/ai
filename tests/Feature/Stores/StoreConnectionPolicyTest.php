<?php

declare(strict_types=1);

namespace Tests\Feature\Stores;

use App\Domains\Stores\Enums\Platform;
use App\Domains\Stores\Models\StoreConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsPlans;
use Tests\TestCase;

class StoreConnectionPolicyTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPlans;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPlans();
    }

    public function test_user_cannot_disconnect_another_accounts_store(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $connection = StoreConnection::query()->create([
            'account_id' => $owner->account_id,
            'platform' => Platform::Shopify->value,
            'name' => 'Owner Store',
            'domain' => 'owner.myshopify.com',
            'status' => 'active',
        ]);

        $this->assertFalse($intruder->can('delete', $connection));

        $this->actingAs($intruder)
            ->delete(route('stores.destroy', $connection))
            ->assertNotFound();

        $this->assertDatabaseHas('store_connections', ['id' => $connection->id]);
    }

    public function test_scoped_route_binding_returns_not_found_for_other_accounts(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $connection = StoreConnection::query()->create([
            'account_id' => $owner->account_id,
            'platform' => Platform::Shopify->value,
            'name' => 'Owner Store',
            'domain' => 'owner.myshopify.com',
            'status' => 'active',
        ]);

        $this->actingAs($intruder)
            ->put(route('stores.reconnect', $connection), [
                'access_token' => 'shpat_test_token_1234567890',
            ])
            ->assertNotFound();
    }
}
