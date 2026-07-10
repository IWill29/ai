<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\Services;

use App\Domains\Dashboard\Contracts\MetricsReader;
use App\Domains\Stores\DTOs\MetricDTO;
use App\Domains\Stores\Models\StoreConnection;
use DateTimeImmutable;

final class SyncedMetricsReaderAdapter implements MetricsReader
{
    public function __construct(
        private readonly SyncedMetricsReader $reader,
    ) {}

    /**
     * @param  array<int, string>  $storeConnectionIds
     */
    public function forStores(array $storeConnectionIds, DateTimeImmutable $from, DateTimeImmutable $to): MetricDTO
    {
        if ($storeConnectionIds === []) {
            return new MetricDTO(
                revenueMinor: 0,
                ordersCount: 0,
                averageOrderValueMinor: 0,
                newCustomers: 0,
                returningCustomers: 0,
                unfulfilledOrders: 0,
                lowStockProducts: 0,
                currency: 'EUR',
            );
        }

        $store = StoreConnection::query()->findOrFail($storeConnectionIds[0]);
        $dashboard = $this->reader->forStore($store, $from, $to);

        return new MetricDTO(
            revenueMinor: $dashboard->revenueMinor,
            ordersCount: $dashboard->ordersCount,
            averageOrderValueMinor: $dashboard->averageOrderValueMinor,
            newCustomers: $dashboard->newCustomers,
            returningCustomers: $dashboard->returningCustomers,
            unfulfilledOrders: $dashboard->unfulfilledOrders,
            lowStockProducts: $dashboard->lowStockProducts,
            currency: $dashboard->currency,
        );
    }
}
