<?php

declare(strict_types=1);

namespace App\Domains\Stores\Actions;

use App\Domains\Billing\Contracts\BillingService;
use App\Domains\Billing\Exceptions\StoreLimitReachedException;
use App\Domains\Stores\Adapters\Shopify\ShopifyAdapter;
use App\Domains\Stores\Adapters\Shopify\ShopifyClient;
use App\Domains\Stores\Enums\Platform;
use App\Domains\Stores\Exceptions\InvalidCredentialsException;
use App\Domains\Stores\Jobs\InitialBulkSyncJob;
use App\Domains\Stores\Jobs\RegisterShopifyWebhooksJob;
use App\Domains\Stores\Models\StoreConnection;
use App\Domains\Stores\Models\StoreCredential;
use Illuminate\Support\Facades\DB;

final class ConnectShopifyStoreAction
{
    public function __construct(
        private readonly BillingService $billing,
    ) {}

    public function execute(
        string $accountId,
        string $domain,
        string $accessToken,
        string $apiSecret,
        ?string $name,
    ): StoreConnection {
        if (! $this->billing->canConnectStore($accountId)) {
            throw new StoreLimitReachedException('Your plan\'s store limit is reached.');
        }

        $client = new ShopifyClient($domain, $accessToken);

        if (! (new ShopifyAdapter($client))->verifyCredentials()) {
            throw new InvalidCredentialsException('Could not connect — check the domain and token.');
        }

        return DB::transaction(function () use ($accountId, $domain, $accessToken, $apiSecret, $name): StoreConnection {
            $connection = StoreConnection::query()->create([
                'account_id' => $accountId,
                'platform' => Platform::Shopify->value,
                'name' => $name ?? $domain,
                'domain' => strtolower($domain),
                'status' => 'active',
                'meta' => [
                    'sync' => [
                        'state' => 'idle',
                        'entity' => null,
                        'mode' => null,
                        'started_at' => null,
                        'error' => null,
                    ],
                    'webhooks' => [
                        'state' => 'pending',
                        'registered_at' => null,
                        'last_attempt_at' => null,
                        'missing_topics' => [],
                    ],
                ],
            ]);

            StoreCredential::query()->create([
                'store_connection_id' => $connection->id,
                'access_token' => $accessToken,
                'secrets' => ['api_secret' => $apiSecret],
            ]);

            InitialBulkSyncJob::dispatch($connection->id)->afterCommit();
            RegisterShopifyWebhooksJob::dispatch($connection->id)->afterCommit();

            return $connection;
        });
    }
}
