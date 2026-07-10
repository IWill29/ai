<?php

declare(strict_types=1);

namespace Tests\Feature\Sync;

use App\Domains\Stores\Enums\Platform;
use App\Domains\Stores\Jobs\ProcessWebhookEventJob;
use App\Domains\Stores\Models\StoreConnection;
use App\Domains\Stores\Models\StoreCredential;
use App\Domains\Stores\Models\SyncedOrder;
use App\Domains\Stores\Models\WebhookEvent;
use App\Domains\Stores\Services\Sync\MirrorUpserter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\SeedsPlans;
use Tests\TestCase;

class ShopifyWebhookTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPlans;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPlans();
    }

    public function test_accepts_webhook_with_valid_hmac(): void
    {
        Queue::fake();

        $connection = $this->createConnection();
        $body = json_encode(['id' => 1001, 'title' => 'Webhook Product'], JSON_THROW_ON_ERROR);
        $secret = 'test_api_secret_key_12345';
        $hmac = base64_encode(hash_hmac('sha256', $body, $secret, true));

        $this->call(
            'POST',
            route('webhooks.shopify', $connection->id),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_SHOPIFY_HMAC_SHA256' => $hmac,
                'HTTP_X_SHOPIFY_WEBHOOK_ID' => 'evt_123',
                'HTTP_X_SHOPIFY_TOPIC' => 'products/create',
            ],
            $body,
        )->assertOk();

        $this->assertDatabaseHas('webhook_events', [
            'store_connection_id' => $connection->id,
            'external_event_id' => 'evt_123',
            'topic' => 'products/create',
            'status' => 'received',
        ]);

        Queue::assertPushed(ProcessWebhookEventJob::class);
    }

    public function test_rejects_webhook_with_invalid_hmac(): void
    {
        $connection = $this->createConnection();
        $body = json_encode(['id' => 1001], JSON_THROW_ON_ERROR);

        $this->call(
            'POST',
            route('webhooks.shopify', $connection->id),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_SHOPIFY_HMAC_SHA256' => 'invalid',
                'HTTP_X_SHOPIFY_WEBHOOK_ID' => 'evt_123',
                'HTTP_X_SHOPIFY_TOPIC' => 'products/create',
            ],
            $body,
        )->assertUnauthorized();

        $this->assertDatabaseCount('webhook_events', 0);
    }

    public function test_dedupes_webhooks_by_external_event_id(): void
    {
        Queue::fake();

        $connection = $this->createConnection();
        $body = json_encode(['id' => 1001, 'title' => 'Webhook Product'], JSON_THROW_ON_ERROR);
        $secret = 'test_api_secret_key_12345';
        $hmac = base64_encode(hash_hmac('sha256', $body, $secret, true));
        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SHOPIFY_HMAC_SHA256' => $hmac,
            'HTTP_X_SHOPIFY_WEBHOOK_ID' => 'evt_duplicate',
            'HTTP_X_SHOPIFY_TOPIC' => 'products/create',
        ];

        $this->call('POST', route('webhooks.shopify', $connection->id), [], [], [], $headers, $body)->assertOk();
        $this->call('POST', route('webhooks.shopify', $connection->id), [], [], [], $headers, $body)->assertOk();

        $this->assertSame(1, WebhookEvent::query()->count());
        Queue::assertPushed(ProcessWebhookEventJob::class, 1);
    }

    public function test_process_webhook_event_deletes_mirror_row_on_order_delete(): void
    {
        $connection = $this->createConnection();

        SyncedOrder::query()->create([
            'store_connection_id' => $connection->id,
            'external_id' => 'gid://shopify/Order/5001',
            'order_number' => '#5001',
            'total_price_minor' => 1000,
        ]);

        $event = WebhookEvent::query()->create([
            'store_connection_id' => $connection->id,
            'platform' => 'shopify',
            'topic' => 'orders/delete',
            'external_event_id' => 'evt_delete_1',
            'status' => 'received',
            'payload' => ['id' => 5001],
        ]);

        (new ProcessWebhookEventJob($event->id))
            ->handle(app(MirrorUpserter::class));

        $this->assertDatabaseMissing('synced_orders', [
            'store_connection_id' => $connection->id,
            'external_id' => 'gid://shopify/Order/5001',
        ]);

        $event->refresh();
        $this->assertSame('processed', $event->status);
    }

    public function test_process_webhook_event_upserts_product(): void
    {
        $connection = $this->createConnection();

        $event = WebhookEvent::query()->create([
            'store_connection_id' => $connection->id,
            'platform' => 'shopify',
            'topic' => 'products/update',
            'external_event_id' => 'evt_product_1',
            'status' => 'received',
            'payload' => [
                'id' => 2001,
                'title' => 'Webhook Tee',
                'body_html' => '<p>Soft cotton</p>',
                'status' => 'active',
                'handle' => 'webhook-tee',
            ],
        ]);

        (new ProcessWebhookEventJob($event->id))
            ->handle(app(MirrorUpserter::class));

        $this->assertDatabaseHas('synced_products', [
            'store_connection_id' => $connection->id,
            'external_id' => 'gid://shopify/Product/2001',
            'title' => 'Webhook Tee',
            'description' => '<p>Soft cotton</p>',
        ]);
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
