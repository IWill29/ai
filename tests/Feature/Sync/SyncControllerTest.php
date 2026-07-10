<?php

declare(strict_types=1);

namespace Tests\Feature\Sync;

use App\Domains\Stores\Enums\Platform;
use App\Domains\Stores\Jobs\IncrementalSyncJob;
use App\Domains\Stores\Models\StoreConnection;
use App\Domains\Stores\Models\StoreCredential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\SeedsPlans;
use Tests\TestCase;

class SyncControllerTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPlans;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPlans();
    }

    public function test_sync_now_dispatches_incremental_sync_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $connection = $this->createConnection($user->account_id);

        $this->actingAs($user)
            ->post(route('stores.sync', $connection))
            ->assertRedirect()
            ->assertSessionHas('sync', 'started');

        Queue::assertPushed(IncrementalSyncJob::class, fn (IncrementalSyncJob $job) => $job->storeConnectionId === $connection->id);
    }

    public function test_sync_now_returns_already_syncing_when_in_progress(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $connection = $this->createConnection($user->account_id, [
            'meta' => [
                'sync' => [
                    'state' => 'syncing',
                    'entity' => 'products',
                ],
            ],
        ]);

        $this->actingAs($user)
            ->post(route('stores.sync', $connection))
            ->assertRedirect()
            ->assertSessionHas('sync', 'already_syncing');

        Queue::assertNothingPushed();
    }

    public function test_sync_status_returns_json_payload(): void
    {
        $user = User::factory()->create();
        $connection = $this->createConnection($user->account_id, [
            'last_synced_at' => now()->subHour(),
            'meta' => [
                'sync' => [
                    'state' => 'idle',
                    'entity' => null,
                ],
            ],
        ]);

        $this->actingAs($user)
            ->getJson(route('stores.sync-status', $connection))
            ->assertOk()
            ->assertJsonPath('state', 'idle')
            ->assertJsonPath('status', 'active')
            ->assertJsonStructure(['last_synced_at']);
    }

    public function test_sync_status_is_forbidden_for_other_accounts(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $connection = $this->createConnection($owner->account_id);

        $this->actingAs($other)
            ->getJson(route('stores.sync-status', $connection))
            ->assertNotFound();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createConnection(string $accountId, array $attributes = []): StoreConnection
    {
        $connection = StoreConnection::query()->create(array_merge([
            'account_id' => $accountId,
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
