<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\Services;

use App\Domains\Dashboard\DTOs\DashboardMetricsDTO;
use App\Domains\Stores\Models\StoreConnection;
use App\Domains\Stores\Models\SyncedOrder;
use App\Domains\Stores\Models\SyncedProductVariant;
use DateInterval;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class SyncedMetricsReader
{
    private const LOW_STOCK_THRESHOLD = 5;

    /** @var list<string> */
    private const PAID_STATUSES = ['paid', 'partially_paid'];

    /** @var list<string> */
    private const REFUND_STATUSES = ['refunded', 'partially_refunded'];

    /** @var list<string> */
    private const UNFULFILLED_STATUSES = ['unfulfilled', 'partial', 'partially_fulfilled'];

    public function forStore(
        StoreConnection $store,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
    ): DashboardMetricsDTO {
        $periodDays = max(1, $from->diff($to)->days);
        $prevTo = $from;
        $prevFrom = $from->sub(new DateInterval("P{$periodDays}D"));

        $meta = is_array($store->meta) ? $store->meta : [];
        $shop = is_array($meta['shop'] ?? null) ? $meta['shop'] : [];
        $currency = is_string($shop['currency'] ?? null) ? $shop['currency'] : 'EUR';

        $revenue = $this->sumRevenue($store->id, $from, $to);
        $orders = $this->countOrders($store->id, $from, $to);
        $aov = $orders > 0 ? (int) round($revenue / $orders) : 0;

        $prevRevenue = $this->sumRevenue($store->id, $prevFrom, $prevTo);
        $prevOrders = $this->countOrders($store->id, $prevFrom, $prevTo);

        [$newCustomers, $returningCustomers] = $this->countNewVsReturning($store->id, $from, $to);

        return new DashboardMetricsDTO(
            revenueMinor: $revenue,
            revenueChangePercent: $this->percentChange($prevRevenue, $revenue),
            ordersCount: $orders,
            ordersChangePercent: $this->percentChange($prevOrders, $orders),
            averageOrderValueMinor: $aov,
            newCustomers: $newCustomers,
            returningCustomers: $returningCustomers,
            unfulfilledOrders: $this->countUnfulfilled($store->id),
            lowStockProducts: $this->countLowStock($store->id),
            outOfStockProducts: $this->countOutOfStock($store->id),
            currency: $currency,
            from: $from,
            to: $to,
        );
    }

    private function sumRevenue(string $storeId, DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        $paid = (int) SyncedOrder::query()
            ->where('store_connection_id', $storeId)
            ->whereBetween('placed_at', [$from, $to])
            ->whereIn('financial_status', self::PAID_STATUSES)
            ->sum('total_price_minor');

        $refunded = (int) SyncedOrder::query()
            ->where('store_connection_id', $storeId)
            ->whereBetween('placed_at', [$from, $to])
            ->whereIn('financial_status', self::REFUND_STATUSES)
            ->sum('total_price_minor');

        return max(0, $paid - $refunded);
    }

    private function countOrders(string $storeId, DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        return SyncedOrder::query()
            ->where('store_connection_id', $storeId)
            ->whereBetween('placed_at', [$from, $to])
            ->count();
    }

    private function countUnfulfilled(string $storeId): int
    {
        return SyncedOrder::query()
            ->where('store_connection_id', $storeId)
            ->whereIn('fulfillment_status', self::UNFULFILLED_STATUSES)
            ->count();
    }

    private function countLowStock(string $storeId): int
    {
        return SyncedProductVariant::query()
            ->whereHas('syncedProduct', fn ($query) => $query
                ->where('store_connection_id', $storeId)
                ->where('status', 'active'))
            ->where('inventory_quantity', '>', 0)
            ->where('inventory_quantity', '<', self::LOW_STOCK_THRESHOLD)
            ->count();
    }

    private function countOutOfStock(string $storeId): int
    {
        return SyncedProductVariant::query()
            ->whereHas('syncedProduct', fn ($query) => $query
                ->where('store_connection_id', $storeId)
                ->where('status', 'active'))
            ->where(fn ($query) => $query
                ->where('inventory_quantity', '<=', 0)
                ->orWhereNull('inventory_quantity'))
            ->count();
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function countNewVsReturning(string $storeId, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $fromString = $from->format('Y-m-d H:i:sP');
        $toString = $to->format('Y-m-d H:i:sP');

        $newCustomers = (int) DB::scalar(
            <<<'SQL'
            SELECT COUNT(DISTINCT synced_customer_id)
            FROM synced_orders
            WHERE store_connection_id = ?
              AND synced_customer_id IS NOT NULL
              AND placed_at BETWEEN ? AND ?
              AND NOT EXISTS (
                SELECT 1
                FROM synced_orders prior
                WHERE prior.store_connection_id = synced_orders.store_connection_id
                  AND prior.synced_customer_id = synced_orders.synced_customer_id
                  AND prior.placed_at < ?
              )
            SQL,
            [$storeId, $fromString, $toString, $fromString],
        );

        $returningCustomers = (int) DB::scalar(
            <<<'SQL'
            SELECT COUNT(DISTINCT synced_customer_id)
            FROM synced_orders
            WHERE store_connection_id = ?
              AND synced_customer_id IS NOT NULL
              AND placed_at BETWEEN ? AND ?
              AND EXISTS (
                SELECT 1
                FROM synced_orders prior
                WHERE prior.store_connection_id = synced_orders.store_connection_id
                  AND prior.synced_customer_id = synced_orders.synced_customer_id
                  AND prior.placed_at < ?
              )
            SQL,
            [$storeId, $fromString, $toString, $fromString],
        );

        return [$newCustomers, $returningCustomers];
    }

    private function percentChange(int $previous, int $current): float
    {
        if ($previous === 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
