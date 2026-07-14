<?php

declare(strict_types=1);

namespace Tests\Feature\Stores;

use App\Domains\Stores\Actions\ConnectShopifyStoreAction;
use App\Domains\Stores\Enums\Platform;
use App\Domains\Stores\Exceptions\InvalidCredentialsException;
use App\Domains\Stores\Models\StoreConnection;
use App\Domains\Stores\Models\StoreCredential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\SeedsPlans;
use Tests\TestCase;

class ConnectShopifyStoreTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPlans;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPlans();
    }

    public function test_connect_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('stores.connect'))
            ->assertOk();
    }

    public function test_connects_a_valid_shopify_store(): void
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

        $connection = StoreConnection::query()
            ->where('account_id', $user->account_id)
            ->first();

        $this->assertNotNull($connection);
        $this->assertSame('active', $connection->status);
        $this->assertSame(Platform::Shopify->value, $connection->platform);

        $credential = StoreCredential::query()
            ->where('store_connection_id', $connection->id)
            ->first();

        $this->assertNotNull($credential);
        $this->assertSame('shpat_test_token_1234567890', $credential->access_token);
        $this->assertNotSame('shpat_test_token_1234567890', $credential->getRawOriginal('access_token'));
        $this->assertSame('test_api_secret_key_12345', $credential->secrets['api_secret']);
        $this->assertStringNotContainsString('test_api_secret_key_12345', (string) $credential->getRawOriginal('secrets'));
    }

    public function test_rejects_an_invalid_token(): void
    {
        Http::fake([
            '*/graphql.json' => Http::response(status: 401),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('stores.connect'))
            ->post(route('stores.store'), [
                'domain' => 'demo.myshopify.com',
                'access_token' => 'shpat_invalid_token_123456789',
                'api_secret' => 'test_api_secret_key_12345',
            ])
            ->assertSessionHasErrors('access_token');

        $this->assertSame(0, StoreConnection::query()->count());
    }

    public function test_enforces_plan_store_limits(): void
    {
        Queue::fake();

        Http::fake([
            '*/graphql.json' => Http::response([
                'data' => ['shop' => ['name' => 'Demo Store']],
            ]),
        ]);

        $user = User::factory()->create();

        StoreConnection::query()->create([
            'account_id' => $user->account_id,
            'platform' => Platform::Shopify->value,
            'name' => 'Existing',
            'domain' => 'existing.myshopify.com',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->from(route('stores.connect'))
            ->post(route('stores.store'), [
                'domain' => 'second.myshopify.com',
                'access_token' => 'shpat_test_token_1234567890',
                'api_secret' => 'test_api_secret_key_12345',
            ])
            ->assertSessionHasErrors('domain');

        $this->assertSame(1, StoreConnection::query()->count());
    }

    public function test_connect_action_throws_for_invalid_credentials(): void
    {
        Http::fake([
            '*/graphql.json' => Http::response(status: 401),
        ]);

        $user = User::factory()->create();

        $this->expectException(InvalidCredentialsException::class);

        app(ConnectShopifyStoreAction::class)->execute(
            accountId: $user->account_id,
            userId: $user->id,
            domain: 'demo.myshopify.com',
            accessToken: 'shpat_invalid_token_123456789',
            apiSecret: 'test_api_secret_key_12345',
            name: null,
        );
    }
}
