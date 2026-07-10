<?php

declare(strict_types=1);

namespace App\Domains\Stores\Services;

use App\Domains\Stores\Models\StoreConnection;

interface SyncService
{
    public function runBulkSync(StoreConnection $connection): void;

    public function runIncrementalSync(StoreConnection $connection): void;

    public function setSyncProgress(StoreConnection $connection, string $entity, string $mode = 'incremental'): void;

    public function markSyncComplete(StoreConnection $connection): void;

    public function markSyncFailed(StoreConnection $connection, string $error): void;

    public function markConnectionError(StoreConnection $connection, string $error): void;
}
