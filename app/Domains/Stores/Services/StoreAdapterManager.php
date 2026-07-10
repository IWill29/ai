<?php

declare(strict_types=1);

namespace App\Domains\Stores\Services;

use App\Domains\Stores\Adapters\Shopify\ShopifyAdapter;
use App\Domains\Stores\Adapters\Shopify\ShopifyClient;
use App\Domains\Stores\Contracts\StoreAdapterFactory;
use App\Domains\Stores\Contracts\StorePort;
use App\Domains\Stores\Enums\Platform;
use App\Domains\Stores\Exceptions\UnsupportedPlatformException;
use App\Domains\Stores\Models\StoreConnection;

final class StoreAdapterManager implements StoreAdapterFactory
{
    public function for(StoreConnection $connection): StorePort
    {
        $connection->loadMissing('credential');

        $platform = Platform::tryFrom($connection->platform);

        if ($platform === null) {
            throw new UnsupportedPlatformException("Unknown platform: {$connection->platform}");
        }

        $credential = $connection->credential;

        if ($credential === null || $credential->access_token === '') {
            throw new UnsupportedPlatformException('Store credentials are missing.');
        }

        return match ($platform) {
            Platform::Shopify => new ShopifyAdapter(
                new ShopifyClient(
                    $connection->domain,
                    $credential->access_token,
                    $connection->id,
                ),
            ),
            Platform::WooCommerce => throw new UnsupportedPlatformException('WooCommerce is v2'),
        };
    }
}
