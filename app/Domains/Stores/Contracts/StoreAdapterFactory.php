<?php

declare(strict_types=1);

namespace App\Domains\Stores\Contracts;

use App\Domains\Stores\Models\StoreConnection;

/**
 * Resolves the correct StorePort adapter for a connection's platform at runtime.
 */
interface StoreAdapterFactory
{
    public function for(StoreConnection $connection): StorePort;
}
