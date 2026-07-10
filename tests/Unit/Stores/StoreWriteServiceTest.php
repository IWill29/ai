<?php

declare(strict_types=1);

namespace Tests\Unit\Stores;

use App\Domains\Billing\Models\AuditLog;
use App\Domains\Stores\Actions\RecordStoreWriteAuditAction;
use App\Domains\Stores\Contracts\StorePort;
use App\Domains\Stores\Enums\Platform;
use App\Domains\Stores\Models\StoreConnection;
use App\Domains\Stores\Models\StoreCredential;
use App\Domains\Stores\Services\StoreAdapterManager;
use App\Domains\Stores\Services\StoreWriteService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\SeedsPlans;
use Tests\TestCase;

class StoreWriteServiceTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPlans;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPlans();
    }

    public function test_write_service_records_audit_log_after_successful_write(): void
    {
        Http::fake([
            '*/graphql.json' => Http::response([
                'data' => [
                    'productUpdate' => [
                        'product' => [
                            'id' => 'gid://shopify/Product/1',
                            'title' => 'Updated',
                            'descriptionHtml' => null,
                            'status' => 'ACTIVE',
                            'handle' => 'updated',
                            'variants' => ['edges' => []],
                            'media' => ['edges' => []],
                        ],
                        'userErrors' => [],
                    ],
                ],
            ]),
        ]);

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

        $service = new StoreWriteService(
            new StoreAdapterManager,
            app(RecordStoreWriteAuditAction::class),
        );

        $service->execute(
            connection: $connection,
            user: $user,
            action: 'store.product.update',
            write: fn (StorePort $adapter) => $adapter->updateProduct(
                'gid://shopify/Product/1',
                ['title' => 'Updated'],
            ),
            context: ['external_id' => 'gid://shopify/Product/1', 'title' => 'Updated'],
        );

        $this->assertDatabaseHas('audit_logs', [
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'store_connection_id' => $connection->id,
            'action' => 'store.product.update',
        ]);

        $audit = AuditLog::query()->first();
        $this->assertSame('Updated', $audit?->context['title'] ?? null);
    }
}
