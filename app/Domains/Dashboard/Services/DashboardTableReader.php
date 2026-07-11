<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\Services;

use App\Domains\Dashboard\DTOs\RecentOrderRowDTO;
use App\Domains\Dashboard\DTOs\TopProductRowDTO;
use App\Domains\Stores\Models\StoreConnection;
use App\Domains\Stores\Models\SyncedOrder;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class DashboardTableReader
{
    /**
     * @return array<int, TopProductRowDTO>
     */
    public function topProducts(
        StoreConnection $store,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
        int $limit = 10,
    ): array {
        $currency = $this->storeCurrency($store);

        $rows = DB::select(
            <<<'SQL'
            SELECT
                COALESCE(item->>'externalId', item->>'title', 'unknown') AS external_id,
                COALESCE(item->>'title', 'Unknown product') AS title,
                SUM((item->>'quantity')::int) AS units_sold,
                SUM((item->>'quantity')::int * (item->>'priceMinor')::bigint) AS revenue_minor
            FROM synced_orders,
                jsonb_array_elements(line_items) AS item
            WHERE store_connection_id = ?
              AND placed_at BETWEEN ? AND ?
              AND line_items IS NOT NULL
            GROUP BY 1, 2
            ORDER BY units_sold DESC
            LIMIT ?
            SQL,
            [
                $store->id,
                $from->format('Y-m-d H:i:sP'),
                $to->format('Y-m-d H:i:sP'),
                $limit,
            ],
        );

        /** @var list<object{external_id: string, title: string, units_sold: int|string, revenue_minor: int|string}> $rows */
        return array_map(
            fn (object $row): TopProductRowDTO => new TopProductRowDTO(
                externalId: (string) $row->external_id,
                title: (string) $row->title,
                unitsSold: (int) $row->units_sold,
                revenueMinor: (int) $row->revenue_minor,
                currency: $currency,
            ),
            $rows,
        );
    }

    /**
     * @return array<int, RecentOrderRowDTO>
     */
    public function recentOrders(StoreConnection $store, int $limit = 10): array
    {
        $defaultCurrency = $this->storeCurrency($store);

        return SyncedOrder::query()
            ->where('store_connection_id', $store->id)
            ->orderByDesc('placed_at')
            ->limit($limit)
            ->get()
            ->map(function (SyncedOrder $order) use ($defaultCurrency): RecentOrderRowDTO {
                $placedAt = $order->placed_at;

                if (! $placedAt instanceof \DateTimeInterface) {
                    $placedAt = now();
                }

                return new RecentOrderRowDTO(
                    externalId: $order->external_id,
                    orderNumber: $order->order_number,
                    totalPriceMinor: $order->total_price_minor,
                    currency: $order->currency ?? $defaultCurrency,
                    financialStatus: $order->financial_status,
                    fulfillmentStatus: $order->fulfillment_status,
                    placedAt: DateTimeImmutable::createFromInterface($placedAt),
                );
            })
            ->all();
    }

    private function storeCurrency(StoreConnection $store): string
    {
        $meta = is_array($store->meta) ? $store->meta : [];
        $shop = is_array($meta['shop'] ?? null) ? $meta['shop'] : [];

        return is_string($shop['currency'] ?? null) ? $shop['currency'] : 'EUR';
    }
}
