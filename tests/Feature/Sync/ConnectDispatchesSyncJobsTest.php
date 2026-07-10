<?php

declare(strict_types=1);

namespace Tests\Feature\Sync;

use App\Domains\Stores\Jobs\InitialBulkSyncJob;
use App\Domains\Stores\Jobs\RegisterShopifyWebhooksJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\SeedsPlans;
use Tests\TestCase;

class ConnectDispatchesSyncJobsTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPlans;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPlans();
    }

    public function test_connect_dispatches_bulk_sync_and_webhook_registration_jobs(): void
    {
        Queue::fake();

        Http::fake([
            '*/graphql.json' => Http::response([
                'data' => ['shop' => ['name' => 'Demo Store']],
            ]),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('stores.store'), [
                'domain' => 'demo.myshopify.com',
                'access_token' => 'shpat_test_token_1234567890',
                'api_secret' => 'test_api_secret_key_12345',
                'name' => 'Demo Store',
            ])
            ->assertRedirect(route('stores.index'));

        Queue::assertPushed(InitialBulkSyncJob::class);
        Queue::assertPushed(RegisterShopifyWebhooksJob::class);
    }
}
