<?php

declare(strict_types=1);

namespace App\Domains\Stores\Services\Sync;

use App\Domains\Stores\Adapters\Shopify\Gid;
use App\Domains\Stores\Adapters\Shopify\ShopifyNormalizer;
use App\Domains\Stores\DTOs\CustomerDTO;
use App\Domains\Stores\DTOs\OrderDTO;
use App\Domains\Stores\DTOs\ProductDTO;
use App\Domains\Stores\Models\StoreConnection;
use App\Domains\Stores\Models\SyncedCustomer;
use App\Domains\Stores\Models\SyncedOrder;
use App\Domains\Stores\Models\SyncedProduct;
use App\Domains\Stores\Models\SyncedProductVariant;
use App\Domains\Stores\Models\WebhookEvent;
use InvalidArgumentException;

final class MirrorUpserter
{
    public function __construct(
        private readonly ShopifyNormalizer $normalizer = new ShopifyNormalizer,
    ) {}

    public function upsert(StoreConnection $connection, string $entity, ProductDTO|OrderDTO|CustomerDTO $dto): void
    {
        match ($entity) {
            'products' => $this->upsertProduct($connection, $dto instanceof ProductDTO ? $dto : throw new InvalidArgumentException('Expected ProductDTO')),
            'orders' => $this->upsertOrder($connection, $dto instanceof OrderDTO ? $dto : throw new InvalidArgumentException('Expected OrderDTO')),
            'customers' => $this->upsertCustomer($connection, $dto instanceof CustomerDTO ? $dto : throw new InvalidArgumentException('Expected CustomerDTO')),
            default => throw new InvalidArgumentException("Unknown entity: {$entity}"),
        };
    }

    /**
     * @param  array<string, mixed>  $node
     */
    public function upsertFromBulkNode(StoreConnection $connection, string $entity, array $node): void
    {
        if (isset($node['__parentId'])) {
            return;
        }

        match ($entity) {
            'products' => $this->upsertProduct($connection, $this->normalizer->toProductDTO($node), $node),
            'customers' => $this->upsertCustomer($connection, $this->normalizer->toCustomerDTO($node), $node),
            'orders' => $this->upsertOrder($connection, $this->normalizer->toOrderDTO($node), $node),
            default => throw new InvalidArgumentException("Unknown entity: {$entity}"),
        };
    }

    public function upsertFromWebhookPayload(StoreConnection $connection, WebhookEvent $event): void
    {
        /** @var array<string, mixed> $payload */
        $payload = $event->payload;

        match (true) {
            str_starts_with($event->topic, 'orders/') => $this->upsertOrder(
                $connection,
                $this->normalizer->toOrderDTO($this->normalizeWebhookOrderPayload($payload)),
                $payload,
            ),
            str_starts_with($event->topic, 'products/') => $this->upsertProduct(
                $connection,
                $this->normalizer->toProductDTO($this->normalizeWebhookProductPayload($payload)),
                $payload,
            ),
            str_starts_with($event->topic, 'customers/') => $this->upsertCustomer(
                $connection,
                $this->normalizer->toCustomerDTO($this->normalizeWebhookCustomerPayload($payload)),
                $payload,
            ),
            default => throw new InvalidArgumentException("Unsupported webhook topic: {$event->topic}"),
        };
    }

    public function deleteFromWebhookPayload(StoreConnection $connection, WebhookEvent $event): void
    {
        /** @var array<string, mixed> $payload */
        $payload = $event->payload;

        $entity = match (true) {
            str_starts_with($event->topic, 'orders/') => 'order',
            str_starts_with($event->topic, 'products/') => 'product',
            str_starts_with($event->topic, 'customers/') => 'customer',
            default => throw new InvalidArgumentException("Unsupported webhook topic: {$event->topic}"),
        };

        $externalId = isset($payload['admin_graphql_api_id'])
            ? (string) $payload['admin_graphql_api_id']
            : Gid::ensureGid((string) ($payload['id'] ?? ''), ucfirst($entity));

        $this->deleteByExternalId($connection, $entity, $externalId);
    }

    public function deleteByExternalId(StoreConnection $connection, string $entity, string $externalId): void
    {
        if ($entity === 'order') {
            SyncedOrder::query()
                ->where('store_connection_id', $connection->id)
                ->where('external_id', Gid::ensureGid($externalId, 'Order'))
                ->delete();

            return;
        }

        if ($entity === 'product') {
            $this->deleteProduct($connection, Gid::ensureGid($externalId, 'Product'));

            return;
        }

        if ($entity === 'customer') {
            SyncedCustomer::query()
                ->where('store_connection_id', $connection->id)
                ->where('external_id', Gid::ensureGid($externalId, 'Customer'))
                ->delete();

            return;
        }

        throw new InvalidArgumentException("Unknown entity: {$entity}");
    }

    /**
     * @param  array<string, mixed>|null  $raw
     */
    public function upsertProduct(StoreConnection $connection, ProductDTO $dto, ?array $raw = null): void
    {
        $product = SyncedProduct::query()->updateOrCreate(
            [
                'store_connection_id' => $connection->id,
                'external_id' => Gid::ensureGid($dto->externalId, 'Product'),
            ],
            [
                'title' => $dto->title,
                'description' => $dto->description,
                'status' => $dto->status,
                'handle' => $dto->handle,
                'raw' => $raw ?? ['imageUrls' => $dto->imageUrls],
                'platform_created_at' => $raw['createdAt'] ?? null,
                'platform_updated_at' => $raw['updatedAt'] ?? null,
            ],
        );

        $seenVariantIds = [];

        foreach ($dto->variants as $variant) {
            $variantGid = Gid::ensureGid($variant->externalId, 'ProductVariant');
            $seenVariantIds[] = $variantGid;

            SyncedProductVariant::query()->updateOrCreate(
                [
                    'synced_product_id' => $product->id,
                    'external_id' => $variantGid,
                ],
                [
                    'sku' => $variant->sku,
                    'title' => $variant->title,
                    'price_minor' => $variant->priceMinor,
                    'currency' => $variant->currency,
                    'inventory_quantity' => $variant->inventoryQuantity,
                ],
            );
        }

        if ($seenVariantIds !== []) {
            SyncedProductVariant::query()
                ->where('synced_product_id', $product->id)
                ->whereNotIn('external_id', $seenVariantIds)
                ->delete();
        }
    }

    /**
     * @param  array<string, mixed>|null  $raw
     */
    public function upsertOrder(StoreConnection $connection, OrderDTO $dto, ?array $raw = null): void
    {
        $syncedCustomerId = null;

        if ($dto->customerExternalId !== null) {
            $customer = SyncedCustomer::query()->firstOrCreate(
                [
                    'store_connection_id' => $connection->id,
                    'external_id' => Gid::ensureGid($dto->customerExternalId, 'Customer'),
                ],
                [
                    'email' => null,
                    'name' => null,
                    'orders_count' => 0,
                    'total_spent_minor' => 0,
                ],
            );

            $syncedCustomerId = $customer->id;
        }

        SyncedOrder::query()->updateOrCreate(
            [
                'store_connection_id' => $connection->id,
                'external_id' => Gid::ensureGid($dto->externalId, 'Order'),
            ],
            [
                'synced_customer_id' => $syncedCustomerId,
                'order_number' => $dto->orderNumber,
                'financial_status' => $dto->financialStatus,
                'fulfillment_status' => $dto->fulfillmentStatus,
                'total_price_minor' => $dto->totalPriceMinor,
                'currency' => $dto->currency,
                'line_items' => array_map(
                    fn ($item) => [
                        'externalId' => $item->externalId,
                        'title' => $item->title,
                        'quantity' => $item->quantity,
                        'priceMinor' => $item->priceMinor,
                        'currency' => $item->currency,
                    ],
                    $dto->lineItems,
                ),
                'raw' => $raw,
                'placed_at' => $dto->placedAt,
            ],
        );
    }

    /**
     * @param  array<string, mixed>|null  $raw
     */
    public function upsertCustomer(StoreConnection $connection, CustomerDTO $dto, ?array $raw = null): void
    {
        SyncedCustomer::query()->updateOrCreate(
            [
                'store_connection_id' => $connection->id,
                'external_id' => Gid::ensureGid($dto->externalId, 'Customer'),
            ],
            [
                'email' => $dto->email,
                'name' => $dto->name,
                'orders_count' => $dto->ordersCount,
                'total_spent_minor' => $dto->totalSpentMinor,
                'currency' => $dto->currency,
                'raw' => $raw,
            ],
        );
    }

    private function deleteProduct(StoreConnection $connection, string $externalId): void
    {
        $product = SyncedProduct::query()
            ->where('store_connection_id', $connection->id)
            ->where('external_id', $externalId)
            ->first();

        if ($product === null) {
            return;
        }

        SyncedProductVariant::query()
            ->where('synced_product_id', $product->id)
            ->delete();

        $product->delete();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizeWebhookOrderPayload(array $payload): array
    {
        return [
            'id' => $payload['admin_graphql_api_id'] ?? Gid::ensureGid((string) ($payload['id'] ?? ''), 'Order'),
            'name' => $payload['name'] ?? null,
            'displayFinancialStatus' => $payload['financial_status'] ?? null,
            'displayFulfillmentStatus' => $payload['fulfillment_status'] ?? null,
            'totalPriceSet' => [
                'shopMoney' => [
                    'amount' => (string) ($payload['total_price'] ?? '0'),
                    'currencyCode' => $payload['currency'] ?? null,
                ],
            ],
            'customer' => isset($payload['customer']['id'])
                ? ['id' => Gid::ensureGid((string) $payload['customer']['id'], 'Customer')]
                : null,
            'createdAt' => $payload['created_at'] ?? null,
            'lineItems' => ['edges' => []],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizeWebhookProductPayload(array $payload): array
    {
        return [
            'id' => $payload['admin_graphql_api_id'] ?? Gid::ensureGid((string) ($payload['id'] ?? ''), 'Product'),
            'title' => $payload['title'] ?? '',
            'descriptionHtml' => $payload['body_html'] ?? null,
            'status' => isset($payload['status']) ? strtoupper((string) $payload['status']) : null,
            'handle' => $payload['handle'] ?? null,
            'variants' => ['edges' => []],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizeWebhookCustomerPayload(array $payload): array
    {
        return [
            'id' => $payload['admin_graphql_api_id'] ?? Gid::ensureGid((string) ($payload['id'] ?? ''), 'Customer'),
            'email' => $payload['email'] ?? null,
            'firstName' => $payload['first_name'] ?? null,
            'lastName' => $payload['last_name'] ?? null,
            'numberOfOrders' => $payload['orders_count'] ?? 0,
            'amountSpent' => [
                'amount' => (string) ($payload['total_spent'] ?? '0'),
                'currencyCode' => $payload['currency'] ?? null,
            ],
        ];
    }
}
