<?php

declare(strict_types=1);

namespace App\Domains\Stores\Services\Sync;

use App\Domains\Stores\Adapters\Shopify\ShopifyAdapter;
use App\Domains\Stores\Adapters\Shopify\ShopifyClient;
use App\Domains\Stores\DTOs\OrderQuery;
use App\Domains\Stores\Models\StoreConnection;
use App\Domains\Stores\Services\SyncService;
use InvalidArgumentException;

final class IncrementalSyncRunner
{
    public function __construct(
        private readonly MirrorUpserter $upserter,
    ) {}

    public function run(ShopifyClient $client, StoreConnection $connection, SyncService $sync): void
    {
        $adapter = new ShopifyAdapter($client, $connection->id);
        $since = $connection->last_synced_at?->toIso8601String()
            ?? now()->subYears(10)->toIso8601String();

        foreach (['products', 'customers', 'orders'] as $entity) {
            $sync->setSyncProgress($connection, $entity, 'incremental');
            $this->syncEntity($adapter, $connection, $entity, $since);
        }
    }

    private function syncEntity(
        ShopifyAdapter $adapter,
        StoreConnection $connection,
        string $entity,
        string $since,
    ): void {
        $cursor = null;
        $search = "updated_at:>={$since}";

        do {
            $result = match ($entity) {
                'products' => $adapter->listProducts(search: $search, limit: 50, cursor: $cursor),
                'customers' => $adapter->listCustomers(search: $search, limit: 50, cursor: $cursor),
                'orders' => $adapter->listOrders(new OrderQuery(
                    updatedSince: new \DateTimeImmutable($since),
                    limit: 50,
                    cursor: $cursor,
                )),
                default => throw new InvalidArgumentException("Unknown entity: {$entity}"),
            };

            foreach ($result->items as $dto) {
                $this->upserter->upsert($connection, $entity, $dto);
            }

            $cursor = $result->nextCursor;
        } while ($result->hasMore);
    }
}
