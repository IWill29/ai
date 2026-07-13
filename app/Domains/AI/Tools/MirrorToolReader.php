<?php

declare(strict_types=1);

namespace App\Domains\AI\Tools;

use App\Domains\Dashboard\Contracts\MetricsReader;
use App\Domains\Stores\Models\StoreConnection;
use App\Domains\Stores\Models\SyncedCustomer;
use App\Domains\Stores\Models\SyncedOrder;
use App\Domains\Stores\Models\SyncedProduct;
use App\Domains\Stores\Models\SyncedProductVariant;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;

final class MirrorToolReader
{
    public function __construct(
        private readonly MetricsReader $metricsReader,
    ) {}

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function read(StoreConnection $store, string $toolName, array $args): array
    {
        return match ($toolName) {
            'list_orders' => $this->listOrders($store, $args),
            'get_order' => $this->getOrder($store, $args),
            'list_products' => $this->listProducts($store, $args),
            'get_product' => $this->getProduct($store, $args),
            'list_customers' => $this->listCustomers($store, $args),
            'get_customer' => $this->getCustomer($store, $args),
            'get_metrics' => $this->getMetrics($store, $args),
            default => throw new \InvalidArgumentException("Unknown read tool [{$toolName}]."),
        };
    }

    /** @param array<string, mixed> $args */
    private function listOrders(StoreConnection $store, array $args): array
    {
        $limit = min((int) ($args['limit'] ?? 25), 50);

        $query = SyncedOrder::query()
            ->where('store_connection_id', $store->id)
            ->when(
                isset($args['fulfillment_status']),
                fn (Builder $q) => $q->where('fulfillment_status', $args['fulfillment_status']),
            )
            ->when(
                isset($args['financial_status']),
                fn (Builder $q) => $q->where('financial_status', $args['financial_status']),
            )
            ->when(
                isset($args['placed_after']),
                fn (Builder $q) => $q->where('placed_at', '>=', $args['placed_after']),
            )
            ->when(
                isset($args['min_total']),
                fn (Builder $q) => $q->where('total_price_minor', '>=', (int) round(((float) $args['min_total']) * 100)),
            )
            ->when(
                isset($args['search']) && $args['search'] !== '',
                fn (Builder $q) => $q->where(function (Builder $inner) use ($args): void {
                    $search = '%'.$args['search'].'%';
                    $inner->where('order_number', 'ilike', $search)
                        ->orWhere('external_id', 'ilike', $search);
                }),
            )
            ->orderByDesc('placed_at')
            ->limit($limit);

        $orders = $query->get()->map(fn (SyncedOrder $order) => $this->serializeOrder($order))->all();

        return ['items' => $orders, 'count' => count($orders)];
    }

    /** @param array<string, mixed> $args */
    private function getOrder(StoreConnection $store, array $args): array
    {
        $order = SyncedOrder::query()
            ->where('store_connection_id', $store->id)
            ->where('external_id', $args['external_id'])
            ->firstOrFail();

        return $this->serializeOrder($order, detailed: true);
    }

    /** @param array<string, mixed> $args */
    private function listProducts(StoreConnection $store, array $args): array
    {
        $limit = min((int) ($args['limit'] ?? 25), 50);

        $products = SyncedProduct::query()
            ->where('store_connection_id', $store->id)
            ->when(isset($args['status']), fn (Builder $q) => $q->where('status', $args['status']))
            ->when(
                isset($args['search']) && $args['search'] !== '',
                fn (Builder $q) => $q->where(function (Builder $inner) use ($args): void {
                    $search = '%'.$args['search'].'%';
                    $inner->where('title', 'ilike', $search)
                        ->orWhere('external_id', 'ilike', $search)
                        ->orWhere('handle', 'ilike', $search);
                }),
            )
            ->orderByDesc('platform_updated_at')
            ->limit($limit)
            ->get()
            ->map(fn (SyncedProduct $product) => $this->serializeProduct($product))
            ->all();

        return ['items' => $products, 'count' => count($products)];
    }

    /** @param array<string, mixed> $args */
    private function getProduct(StoreConnection $store, array $args): array
    {
        $product = SyncedProduct::query()
            ->where('store_connection_id', $store->id)
            ->where('external_id', $args['external_id'])
            ->with('variants')
            ->firstOrFail();

        return $this->serializeProduct($product, detailed: true);
    }

    /** @param array<string, mixed> $args */
    private function listCustomers(StoreConnection $store, array $args): array
    {
        $limit = min((int) ($args['limit'] ?? 25), 50);

        $customers = SyncedCustomer::query()
            ->where('store_connection_id', $store->id)
            ->when(
                isset($args['search']) && $args['search'] !== '',
                fn (Builder $q) => $q->where(function (Builder $inner) use ($args): void {
                    $search = '%'.$args['search'].'%';
                    $inner->where('email', 'ilike', $search)
                        ->orWhere('name', 'ilike', $search)
                        ->orWhere('external_id', 'ilike', $search);
                }),
            )
            ->orderByDesc('total_spent_minor')
            ->limit($limit)
            ->get()
            ->map(fn (SyncedCustomer $customer) => $this->serializeCustomer($customer))
            ->all();

        return ['items' => $customers, 'count' => count($customers)];
    }

    /** @param array<string, mixed> $args */
    private function getCustomer(StoreConnection $store, array $args): array
    {
        $customer = SyncedCustomer::query()
            ->where('store_connection_id', $store->id)
            ->where('external_id', $args['external_id'])
            ->firstOrFail();

        return $this->serializeCustomer($customer, detailed: true);
    }

    /** @param array<string, mixed> $args */
    private function getMetrics(StoreConnection $store, array $args): array
    {
        $from = new DateTimeImmutable($args['from']);
        $to = new DateTimeImmutable($args['to']);

        $metrics = $this->metricsReader->forStores([$store->id], $from, $to);

        return [
            'revenue_minor' => $metrics->revenueMinor,
            'orders_count' => $metrics->ordersCount,
            'average_order_value_minor' => $metrics->averageOrderValueMinor,
            'new_customers' => $metrics->newCustomers,
            'returning_customers' => $metrics->returningCustomers,
            'unfulfilled_orders' => $metrics->unfulfilledOrders,
            'low_stock_products' => $metrics->lowStockProducts,
            'currency' => $metrics->currency,
        ];
    }

    /** @return array<string, mixed> */
    private function serializeOrder(SyncedOrder $order, bool $detailed = false): array
    {
        $data = [
            'external_id' => $order->external_id,
            'order_number' => $order->order_number,
            'financial_status' => $order->financial_status,
            'fulfillment_status' => $order->fulfillment_status,
            'total_price_minor' => $order->total_price_minor,
            'currency' => $order->currency,
            'placed_at' => $order->placed_at?->toIso8601String(),
        ];

        if ($detailed) {
            $data['line_items'] = $order->line_items;
            $data['raw'] = $order->raw;
        }

        return $data;
    }

    /** @return array<string, mixed> */
    private function serializeProduct(SyncedProduct $product, bool $detailed = false): array
    {
        $data = [
            'external_id' => $product->external_id,
            'title' => $product->title,
            'description' => $product->description,
            'status' => $product->status,
            'handle' => $product->handle,
        ];

        if ($detailed) {
            $data['variants'] = $product->variants
                ->map(fn (SyncedProductVariant $variant) => [
                    'external_id' => $variant->external_id,
                    'sku' => $variant->sku,
                    'title' => $variant->title,
                    'price_minor' => $variant->price_minor,
                    'currency' => $variant->currency,
                    'inventory_quantity' => $variant->inventory_quantity,
                ])
                ->all();
        }

        return $data;
    }

    /** @return array<string, mixed> */
    private function serializeCustomer(SyncedCustomer $customer, bool $detailed = false): array
    {
        $data = [
            'external_id' => $customer->external_id,
            'email' => $customer->email,
            'name' => $customer->name,
            'orders_count' => $customer->orders_count,
            'total_spent_minor' => $customer->total_spent_minor,
            'currency' => $customer->currency,
        ];

        if ($detailed) {
            $data['raw'] = $customer->raw;
        }

        return $data;
    }
}
