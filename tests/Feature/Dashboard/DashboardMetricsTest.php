<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Domains\Dashboard\Services\DashboardTableReader;
use App\Domains\Dashboard\Services\SyncedMetricsReader;
use App\Domains\Stores\Enums\Platform;
use App\Domains\Stores\Models\StoreConnection;
use App\Domains\Stores\Models\SyncedCustomer;
use App\Domains\Stores\Models\SyncedOrder;
use App\Domains\Stores\Models\SyncedProduct;
use App\Domains\Stores\Models\SyncedProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsPlans;
use Tests\TestCase;

class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPlans;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPlans();
    }

    public function test_shows_empty_state_when_no_stores_connected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('dashboard')
                ->where('hasStores', false));
    }

    public function test_calculates_revenue_from_synced_mirror(): void
    {
        $user = User::factory()->create();
        $store = $this->createStore($user->account_id);

        SyncedOrder::query()->create([
            'store_connection_id' => $store->id,
            'external_id' => 'gid://shopify/Order/1',
            'total_price_minor' => 5000,
            'financial_status' => 'paid',
            'currency' => 'EUR',
            'placed_at' => now()->subDays(5),
        ]);

        $metrics = app(SyncedMetricsReader::class)->forStore(
            $store,
            now()->subDays(30)->toImmutable(),
            now()->toImmutable(),
        );

        $this->assertSame(5000, $metrics->revenueMinor);
    }

    public function test_counts_unfulfilled_orders_as_snapshot_not_period_scoped(): void
    {
        $user = User::factory()->create();
        $store = $this->createStore($user->account_id);

        SyncedOrder::query()->create([
            'store_connection_id' => $store->id,
            'external_id' => 'gid://shopify/Order/2',
            'total_price_minor' => 1000,
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'currency' => 'EUR',
            'placed_at' => now()->subDays(120),
        ]);

        $metrics = app(SyncedMetricsReader::class)->forStore(
            $store,
            now()->subDays(30)->toImmutable(),
            now()->toImmutable(),
        );

        $this->assertSame(1, $metrics->unfulfilledOrders);
    }

    public function test_calculates_percent_change_vs_previous_period(): void
    {
        $user = User::factory()->create();
        $store = $this->createStore($user->account_id);

        SyncedOrder::query()->create([
            'store_connection_id' => $store->id,
            'external_id' => 'gid://shopify/Order/3',
            'total_price_minor' => 8000,
            'financial_status' => 'paid',
            'currency' => 'EUR',
            'placed_at' => now()->subDays(45),
        ]);

        SyncedOrder::query()->create([
            'store_connection_id' => $store->id,
            'external_id' => 'gid://shopify/Order/4',
            'total_price_minor' => 10000,
            'financial_status' => 'paid',
            'currency' => 'EUR',
            'placed_at' => now()->subDays(5),
        ]);

        $metrics = app(SyncedMetricsReader::class)->forStore(
            $store,
            now()->subDays(30)->toImmutable(),
            now()->toImmutable(),
        );

        $this->assertSame(10000, $metrics->revenueMinor);
        $this->assertSame(25.0, $metrics->revenueChangePercent);
    }

    public function test_scopes_dashboard_to_account_stores_only(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $store = $this->createStore($owner->account_id);

        $this->actingAs($intruder)
            ->get(route('dashboard', ['store_id' => $store->id]))
            ->assertSessionHasErrors('store_id');
    }

    public function test_filter_date_ranges(): void
    {
        $user = User::factory()->create();
        $store = $this->createStore($user->account_id);

        SyncedOrder::query()->create([
            'store_connection_id' => $store->id,
            'external_id' => 'gid://shopify/Order/5',
            'total_price_minor' => 3000,
            'financial_status' => 'paid',
            'currency' => 'EUR',
            'placed_at' => now()->subDays(3),
        ]);

        SyncedOrder::query()->create([
            'store_connection_id' => $store->id,
            'external_id' => 'gid://shopify/Order/6',
            'total_price_minor' => 7000,
            'financial_status' => 'paid',
            'currency' => 'EUR',
            'placed_at' => now()->subDays(20),
        ]);

        $metrics7d = app(SyncedMetricsReader::class)->forStore(
            $store,
            now()->subDays(7)->toImmutable(),
            now()->toImmutable(),
        );

        $metrics30d = app(SyncedMetricsReader::class)->forStore(
            $store,
            now()->subDays(30)->toImmutable(),
            now()->toImmutable(),
        );

        $this->assertSame(3000, $metrics7d->revenueMinor);
        $this->assertSame(10000, $metrics30d->revenueMinor);
    }

    public function test_top_products_aggregates_line_items(): void
    {
        $user = User::factory()->create();
        $store = $this->createStore($user->account_id);

        SyncedOrder::query()->create([
            'store_connection_id' => $store->id,
            'external_id' => 'gid://shopify/Order/7',
            'total_price_minor' => 3000,
            'financial_status' => 'paid',
            'currency' => 'EUR',
            'placed_at' => now()->subDays(2),
            'line_items' => [
                [
                    'externalId' => 'gid://shopify/Product/1',
                    'title' => 'Blue Tee',
                    'quantity' => 2,
                    'priceMinor' => 1000,
                    'currency' => 'EUR',
                ],
            ],
        ]);

        SyncedOrder::query()->create([
            'store_connection_id' => $store->id,
            'external_id' => 'gid://shopify/Order/8',
            'total_price_minor' => 1000,
            'financial_status' => 'paid',
            'currency' => 'EUR',
            'placed_at' => now()->subDays(1),
            'line_items' => [
                [
                    'externalId' => 'gid://shopify/Product/1',
                    'title' => 'Blue Tee',
                    'quantity' => 1,
                    'priceMinor' => 1000,
                    'currency' => 'EUR',
                ],
            ],
        ]);

        $topProducts = app(DashboardTableReader::class)->topProducts(
            $store,
            now()->subDays(30)->toImmutable(),
            now()->toImmutable(),
        );

        $this->assertCount(1, $topProducts);
        $this->assertSame(3, $topProducts[0]->unitsSold);
        $this->assertSame(3000, $topProducts[0]->revenueMinor);
    }

    public function test_counts_new_and_returning_customers(): void
    {
        $user = User::factory()->create();
        $store = $this->createStore($user->account_id);

        $newCustomer = SyncedCustomer::query()->create([
            'store_connection_id' => $store->id,
            'external_id' => 'gid://shopify/Customer/1',
            'email' => 'new@example.com',
        ]);

        $returningCustomer = SyncedCustomer::query()->create([
            'store_connection_id' => $store->id,
            'external_id' => 'gid://shopify/Customer/2',
            'email' => 'returning@example.com',
        ]);

        SyncedOrder::query()->create([
            'store_connection_id' => $store->id,
            'synced_customer_id' => $newCustomer->id,
            'external_id' => 'gid://shopify/Order/9',
            'total_price_minor' => 1000,
            'financial_status' => 'paid',
            'currency' => 'EUR',
            'placed_at' => now()->subDays(3),
        ]);

        SyncedOrder::query()->create([
            'store_connection_id' => $store->id,
            'synced_customer_id' => $returningCustomer->id,
            'external_id' => 'gid://shopify/Order/10',
            'total_price_minor' => 1000,
            'financial_status' => 'paid',
            'currency' => 'EUR',
            'placed_at' => now()->subDays(60),
        ]);

        SyncedOrder::query()->create([
            'store_connection_id' => $store->id,
            'synced_customer_id' => $returningCustomer->id,
            'external_id' => 'gid://shopify/Order/11',
            'total_price_minor' => 2000,
            'financial_status' => 'paid',
            'currency' => 'EUR',
            'placed_at' => now()->subDays(2),
        ]);

        $metrics = app(SyncedMetricsReader::class)->forStore(
            $store,
            now()->subDays(30)->toImmutable(),
            now()->toImmutable(),
        );

        $this->assertSame(1, $metrics->newCustomers);
        $this->assertSame(1, $metrics->returningCustomers);
    }

    public function test_counts_low_and_out_of_stock_variants(): void
    {
        $user = User::factory()->create();
        $store = $this->createStore($user->account_id);

        $product = SyncedProduct::query()->create([
            'store_connection_id' => $store->id,
            'external_id' => 'gid://shopify/Product/99',
            'title' => 'Test Product',
            'status' => 'active',
        ]);

        SyncedProductVariant::query()->create([
            'synced_product_id' => $product->id,
            'external_id' => 'gid://shopify/ProductVariant/1',
            'title' => 'Low stock',
            'inventory_quantity' => 3,
        ]);

        SyncedProductVariant::query()->create([
            'synced_product_id' => $product->id,
            'external_id' => 'gid://shopify/ProductVariant/2',
            'title' => 'Out of stock',
            'inventory_quantity' => 0,
        ]);

        $metrics = app(SyncedMetricsReader::class)->forStore(
            $store,
            now()->subDays(30)->toImmutable(),
            now()->toImmutable(),
        );

        $this->assertSame(1, $metrics->lowStockProducts);
        $this->assertSame(1, $metrics->outOfStockProducts);
    }

    public function test_authenticated_user_with_store_sees_dashboard(): void
    {
        $user = User::factory()->create();
        $this->createStore($user->account_id);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('dashboard')
                ->where('hasStores', true)
                ->has('filters')
                ->has('stores'));
    }

    private function createStore(string $accountId, array $attributes = []): StoreConnection
    {
        return StoreConnection::query()->create(array_merge([
            'account_id' => $accountId,
            'platform' => Platform::Shopify->value,
            'name' => 'Demo Store',
            'domain' => 'demo.myshopify.com',
            'status' => 'active',
            'meta' => ['shop' => ['currency' => 'EUR']],
        ], $attributes));
    }
}
