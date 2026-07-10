<?php

declare(strict_types=1);

namespace App\Domains\Stores\Services;

use App\Domains\Stores\Contracts\StoreAdapterFactory;
use App\Domains\Stores\Contracts\StorePort;
use App\Domains\Stores\Enums\Platform;
use App\Domains\Stores\Exceptions\UnsupportedPlatformException;
use App\Domains\Stores\Models\StoreConnection;

/**
 * Resolves StorePort by platform. Phase 5 adds ShopifyAdapter.
 */
final class StoreAdapterManager implements StoreAdapterFactory
{
    public function __construct(
        private readonly StubStorePort $stubStorePort,
    ) {}

    public function for(StoreConnection $connection): StorePort
    {
        $platform = Platform::tryFrom($connection->platform);

        if ($platform === null) {
            throw new UnsupportedPlatformException("Unknown platform: {$connection->platform}");
        }

        return match ($platform) {
            Platform::Shopify => $this->stubStorePort,
            Platform::WooCommerce => throw new UnsupportedPlatformException('WooCommerce is v2'),
        };
    }
}
