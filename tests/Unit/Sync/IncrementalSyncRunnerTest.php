<?php

declare(strict_types=1);

namespace Tests\Unit\Sync;

use App\Domains\Stores\Adapters\Shopify\ShopifyAdapter;
use App\Domains\Stores\Adapters\Shopify\ShopifyClient;
use App\Domains\Stores\DTOs\OrderQuery;
use App\Domains\Stores\Enums\Platform;
use App\Domains\Stores\Jobs\IncrementalSyncJob;
use App\Domains\Stores\Models\StoreConnection;
use App\Domains\Stores\Models\StoreCredential;
use App\Domains\Stores\Services\DefaultSyncService;
use App\Domains\Stores\Services\Sync\BulkSyncRunner;
use App\Domains\Stores\Services\Sync\IncrementalSyncRunner;
use App\Domains\Stores\Services\Sync\MirrorUpserter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class IncrementalSyncRunnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_incremental_sync_uses_updated_at_filter_and_upserts_products(): void
    {
        Http::fake([
            '*/graphql.json' => Http::sequence()
                ->push([
                    'data' => [
                        'products' => [
                            'edges' => [
                                [
                                    'cursor' => 'cursor1',
                                    'node' => [
                                        'id' => 'gid://shopify/Product/9',
                                        'title' => 'Updated Tee',
                                        'descriptionHtml' => '<p>Fresh stock</p>',
                                        'status' => 'ACTIVE',
                                        'handle' => 'updated-tee',
                                        'variants' => ['edges' => []],
                                        'media' => ['edges' => []],
                                    ],
                                ],
                            ],
                            'pageInfo' => [
                                'hasNextPage' => false,
                                'endCursor' => 'cursor1',
                            ],
                        ],
                    ],
                ])
                ->push([
                    'data' => [
                        'customers' => [
                            'edges' => [],
                            'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
                        ],
                    ],
                ])
                ->push([
                    'data' => [
                        'orders' => [
                            'edges' => [],
                            'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
                        ],
                    ],
                ]),
        ]);

        $connection = $this->createConnection([
            'last_synced_at' => now()->subHour(),
        ]);

        $sync = new DefaultSyncService(
            new BulkSyncRunner(new MirrorUpserter),
            new IncrementalSyncRunner(new MirrorUpserter),
        );

        $sync->runIncrementalSync($connection);

        Http::assertSent(function ($request) {
            $body = $request->data();
            $query = $body['variables']['q'] ?? null;

            return is_string($query) && str_contains($query, 'updated_at:>=');
        });

        $this->assertDatabaseHas('synced_products', [
            'store_connection_id' => $connection->id,
            'external_id' => 'gid://shopify/Product/9',
            'description' => '<p>Fresh stock</p>',
        ]);
    }

    public function test_shopify_adapter_sync_all_dispatches_incremental_job(): void
    {
        Queue::fake();

        $adapter = new ShopifyAdapter(
            client: new ShopifyClient('demo.myshopify.com', 'shpat_test_token_1234567890'),
            connectionId: 'connection-123',
        );

        $adapter->syncAll();

        Queue::assertPushed(IncrementalSyncJob::class, fn (IncrementalSyncJob $job) => $job->storeConnectionId === 'connection-123');
    }

    public function test_order_query_includes_updated_since_in_search_query(): void
    {
        Http::fake([
            '*/graphql.json' => Http::response([
                'data' => [
                    'orders' => [
                        'edges' => [],
                        'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
                    ],
                ],
            ]),
        ]);

        $adapter = new ShopifyAdapter(
            new ShopifyClient('demo.myshopify.com', 'shpat_test_token_1234567890'),
        );

        $since = new \DateTimeImmutable('2026-07-09T12:00:00Z');
        $adapter->listOrders(new OrderQuery(updatedSince: $since, limit: 10));

        Http::assertSent(function ($request) {
            $query = $request->data()['variables']['q'] ?? '';

            return is_string($query) && str_contains($query, 'updated_at:>=');
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createConnection(array $attributes = []): StoreConnection
    {
        $user = User::factory()->create();

        $connection = StoreConnection::query()->create(array_merge([
            'account_id' => $user->account_id,
            'platform' => Platform::Shopify->value,
            'name' => 'Demo Store',
            'domain' => 'demo.myshopify.com',
            'status' => 'active',
        ], $attributes));

        StoreCredential::query()->create([
            'store_connection_id' => $connection->id,
            'access_token' => 'shpat_test_token_1234567890',
            'secrets' => ['api_secret' => 'test_api_secret_key_12345'],
        ]);

        return $connection;
    }
}
