<?php

declare(strict_types=1);

namespace App\Domains\Stores\Actions;

use App\Domains\Billing\Contracts\BillingService;
use App\Domains\Billing\Exceptions\StoreLimitReachedException;
use App\Domains\Stores\Adapters\Shopify\ShopifyAdapter;
use App\Domains\Stores\Adapters\Shopify\ShopifyClient;
use App\Domains\Stores\Enums\Platform;
use App\Domains\Stores\Exceptions\InvalidCredentialsException;
use App\Domains\Stores\Models\StoreConnection;
use App\Domains\Stores\Models\StoreCredential;
use Illuminate\Support\Facades\DB;

final class ConnectShopifyStoreAction
{
    public function __construct(
        private readonly BillingService $billing,
    ) {}

    public function execute(string $accountId, string $domain, string $accessToken, ?string $name): StoreConnection
    {
        if (! $this->billing->canConnectStore($accountId)) {
            throw new StoreLimitReachedException('Your plan\'s store limit is reached.');
        }

        $client = new ShopifyClient($domain, $accessToken);

        if (! (new ShopifyAdapter($client))->verifyCredentials()) {
            throw new InvalidCredentialsException('Could not connect — check the domain and token.');
        }

        return DB::transaction(function () use ($accountId, $domain, $accessToken, $name): StoreConnection {
            $connection = StoreConnection::query()->create([
                'account_id' => $accountId,
                'platform' => Platform::Shopify->value,
                'name' => $name ?? $domain,
                'domain' => strtolower($domain),
                'status' => 'active',
            ]);

            StoreCredential::query()->create([
                'store_connection_id' => $connection->id,
                'access_token' => $accessToken,
            ]);

            return $connection;
        });
    }
}
