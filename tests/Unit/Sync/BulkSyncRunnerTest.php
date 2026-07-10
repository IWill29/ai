<?php

declare(strict_types=1);

namespace Tests\Unit\Sync;

use App\Domains\Stores\Enums\Platform;
use App\Domains\Stores\Models\StoreConnection;
use App\Domains\Stores\Models\StoreCredential;
use App\Domains\Stores\Services\DefaultSyncService;
use App\Domains\Stores\Services\Sync\BulkSyncRunner;
use App\Domains\Stores\Services\Sync\IncrementalSyncRunner;
use App\Domains\Stores\Services\Sync\MirrorUpserter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BulkSyncRunnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_jsonl_into_synced_products_with_description_mapping(): void
    {
        Http::fake([
            '*/graphql.json' => Http::sequence()
                ->push([
                    'data' => [
                        'bulkOperationRunQuery' => [
                            'bulkOperation' => ['id' => 'gid://shopify/BulkOperation/1', 'status' => 'CREATED'],
                            'userErrors' => [],
                        ],
                    ],
                ])
                ->push([
                    'data' => [
                        'bulkOperation' => [
                            'status' => 'COMPLETED',
                            'url' => 'https://bulk.test/products.jsonl',
                        ],
                    ],
                ])
                ->push([
                    'data' => [
                        'bulkOperationRunQuery' => [
                            'bulkOperation' => ['id' => 'gid://shopify/BulkOperation/2', 'status' => 'CREATED'],
                            'userErrors' => [],
                        ],
                    ],
                ])
                ->push([
                    'data' => [
                        'bulkOperation' => [
                            'status' => 'COMPLETED',
                            'url' => 'https://bulk.test/customers.jsonl',
                        ],
                    ],
                ])
                ->push([
                    'data' => [
                        'bulkOperationRunQuery' => [
                            'bulkOperation' => ['id' => 'gid://shopify/BulkOperation/3', 'status' => 'CREATED'],
                            'userErrors' => [],
                        ],
                    ],
                ])
                ->push([
                    'data' => [
                        'bulkOperation' => [
                            'status' => 'COMPLETED',
                            'url' => 'https://bulk.test/orders.jsonl',
                        ],
                    ],
                ]),
            'https://bulk.test/products.jsonl' => Http::response(
                json_encode([
                    'id' => 'gid://shopify/Product/1',
                    'title' => 'Mirror Tee',
                    'descriptionHtml' => '<p>Comfort fit</p>',
                    'status' => 'ACTIVE',
                    'handle' => 'mirror-tee',
                    'createdAt' => '2026-07-10T10:00:00Z',
                    'updatedAt' => '2026-07-10T11:00:00Z',
                    'variants' => ['edges' => []],
                ], JSON_THROW_ON_ERROR)."\n",
            ),
            'https://bulk.test/customers.jsonl' => Http::response(''),
            'https://bulk.test/orders.jsonl' => Http::response(''),
        ]);

        $connection = $this->createConnection();
        $sync = new DefaultSyncService(
            new BulkSyncRunner(new MirrorUpserter),
            new IncrementalSyncRunner(new MirrorUpserter),
        );

        $sync->runBulkSync($connection);

        $this->assertDatabaseHas('synced_products', [
            'store_connection_id' => $connection->id,
            'external_id' => 'gid://shopify/Product/1',
            'title' => 'Mirror Tee',
            'description' => '<p>Comfort fit</p>',
        ]);

        $connection->refresh();
        $this->assertNotNull($connection->last_synced_at);
        $this->assertSame('idle', $connection->meta['sync']['state'] ?? null);
    }

    private function createConnection(): StoreConnection
    {
        $user = User::factory()->create();

        $connection = StoreConnection::query()->create([
            'account_id' => $user->account_id,
            'platform' => Platform::Shopify->value,
            'name' => 'Demo Store',
            'domain' => 'demo.myshopify.com',
            'status' => 'active',
        ]);

        StoreCredential::query()->create([
            'store_connection_id' => $connection->id,
            'access_token' => 'shpat_test_token_1234567890',
            'secrets' => ['api_secret' => 'test_api_secret_key_12345'],
        ]);

        return $connection;
    }
}
