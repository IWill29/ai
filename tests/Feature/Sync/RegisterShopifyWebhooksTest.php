<?php

declare(strict_types=1);

namespace Tests\Feature\Sync;

use App\Domains\Stores\Enums\Platform;
use App\Domains\Stores\Jobs\RegisterShopifyWebhooksJob;
use App\Domains\Stores\Models\StoreConnection;
use App\Domains\Stores\Models\StoreCredential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RegisterShopifyWebhooksTest extends TestCase
{
    use RefreshDatabase;

    public function test_sets_webhooks_pending_when_registration_fails(): void
    {
        Http::fake([
            '*/graphql.json' => Http::response([
                'data' => [
                    'webhookSubscriptionCreate' => [
                        'userErrors' => [
                            ['message' => 'Invalid callback URL'],
                        ],
                    ],
                ],
            ]),
        ]);

        $connection = $this->createConnection();

        (new RegisterShopifyWebhooksJob($connection->id))->handle();

        $connection->refresh();

        $this->assertSame('webhooks_pending', $connection->status);
        $this->assertSame('pending', $connection->meta['webhooks']['state'] ?? null);
        $this->assertNotEmpty($connection->meta['webhooks']['missing_topics'] ?? []);
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
