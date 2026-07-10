<?php

declare(strict_types=1);

namespace App\Domains\Stores\Actions;

use App\Domains\Stores\Adapters\Shopify\ShopifyAdapter;
use App\Domains\Stores\Adapters\Shopify\ShopifyCircuitBreaker;
use App\Domains\Stores\Adapters\Shopify\ShopifyClient;
use App\Domains\Stores\Exceptions\InvalidCredentialsException;
use App\Domains\Stores\Models\StoreConnection;
use Illuminate\Support\Facades\DB;

final class ReconnectShopifyStoreAction
{
    public function execute(StoreConnection $connection, string $accessToken): StoreConnection
    {
        $client = new ShopifyClient($connection->domain, $accessToken, $connection->id);

        if (! (new ShopifyAdapter($client))->verifyCredentials()) {
            throw new InvalidCredentialsException('Could not reconnect — check the token.');
        }

        return DB::transaction(function () use ($connection, $accessToken): StoreConnection {
            $connection->credential()->updateOrCreate(
                ['store_connection_id' => $connection->id],
                ['access_token' => $accessToken],
            );

            $connection->update(['status' => 'active']);

            (new ShopifyCircuitBreaker($connection->id))->recordSuccess();

            return $connection->fresh(['credential']) ?? $connection;
        });
    }
}
