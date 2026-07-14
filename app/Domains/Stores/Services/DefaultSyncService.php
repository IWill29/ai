<?php

declare(strict_types=1);

namespace App\Domains\Stores\Services;

use App\Domains\Stores\Adapters\Shopify\ShopifyClient;
use App\Domains\Stores\Exceptions\InvalidCredentialsException;
use App\Domains\Stores\Exceptions\RateLimitException;
use App\Support\SensitiveData;
use App\Domains\Stores\Models\StoreConnection;
use App\Domains\Stores\Services\Sync\BulkSyncRunner;
use App\Domains\Stores\Services\Sync\IncrementalSyncRunner;

final class DefaultSyncService implements SyncService
{
    public function __construct(
        private readonly BulkSyncRunner $bulkRunner,
        private readonly IncrementalSyncRunner $incrementalRunner,
    ) {}

    public function runBulkSync(StoreConnection $connection): void
    {
        $connection = $this->beginSync($connection, 'bulk');

        try {
            $this->bulkRunner->run($this->clientFor($connection), $connection, $this);
            $this->markSyncComplete($connection);
        } catch (RateLimitException|InvalidCredentialsException $exception) {
            $this->handleSyncException($connection, $exception);

            throw $exception;
        } catch (\Throwable $exception) {
            $this->markSyncFailed($connection, SensitiveData::safeThrowableMessage($exception));

            throw $exception;
        }
    }

    public function runIncrementalSync(StoreConnection $connection): void
    {
        $connection = $this->beginSync($connection, 'incremental');

        try {
            $this->incrementalRunner->run($this->clientFor($connection), $connection, $this);
            $this->markSyncComplete($connection);
        } catch (RateLimitException|InvalidCredentialsException $exception) {
            $this->handleSyncException($connection, $exception);

            throw $exception;
        } catch (\Throwable $exception) {
            $this->markSyncFailed($connection, SensitiveData::safeThrowableMessage($exception));

            throw $exception;
        }
    }

    public function setSyncProgress(StoreConnection $connection, string $entity, string $mode = 'incremental'): void
    {
        $meta = is_array($connection->meta) ? $connection->meta : [];

        $meta['sync'] = [
            'state' => 'syncing',
            'entity' => $entity,
            'mode' => $mode,
            'started_at' => is_array($meta['sync'] ?? null) ? ($meta['sync']['started_at'] ?? now()->toIso8601String()) : now()->toIso8601String(),
            'error' => null,
        ];

        $connection->update(['meta' => $meta]);
        $connection->refresh();
    }

    public function markSyncComplete(StoreConnection $connection): void
    {
        $meta = is_array($connection->meta) ? $connection->meta : [];

        $meta['sync'] = [
            'state' => 'idle',
            'entity' => null,
            'mode' => null,
            'started_at' => null,
            'error' => null,
        ];

        $connection->update([
            'last_synced_at' => now(),
            'meta' => $meta,
        ]);
    }

    public function markSyncFailed(StoreConnection $connection, string $error): void
    {
        $meta = is_array($connection->meta) ? $connection->meta : [];

        $meta['sync'] = array_merge(is_array($meta['sync'] ?? null) ? $meta['sync'] : [], [
            'state' => 'failed',
            'entity' => null,
            'error' => $error,
        ]);

        $connection->update(['meta' => $meta]);
    }

    public function markConnectionError(StoreConnection $connection, string $error): void
    {
        $this->markSyncFailed($connection, $error);
        $connection->update(['status' => 'error']);
    }

    private function beginSync(StoreConnection $connection, string $mode): StoreConnection
    {
        $meta = is_array($connection->meta) ? $connection->meta : [];

        $meta['sync'] = [
            'state' => 'syncing',
            'entity' => null,
            'mode' => $mode,
            'started_at' => now()->toIso8601String(),
            'error' => null,
        ];

        $connection->update(['meta' => $meta]);

        return $connection->fresh() ?? $connection;
    }

    private function clientFor(StoreConnection $connection): ShopifyClient
    {
        $connection->loadMissing('credential');

        $credential = $connection->credential;

        if ($credential === null || $credential->access_token === '') {
            throw new InvalidCredentialsException('Store credentials are missing.');
        }

        return new ShopifyClient(
            $connection->domain,
            $credential->access_token,
            $connection->id,
        );
    }

    private function handleSyncException(StoreConnection $connection, \Throwable $exception): void
    {
        if ($exception instanceof InvalidCredentialsException) {
            $this->markConnectionError($connection, SensitiveData::safeThrowableMessage($exception));
        }
    }
}
