<?php

declare(strict_types=1);

namespace App\Domains\Stores\Contracts;

use App\Domains\Stores\DTOs\CustomerDTO;
use App\Domains\Stores\DTOs\MetricDTO;
use App\Domains\Stores\DTOs\OrderDTO;
use App\Domains\Stores\DTOs\OrderQuery;
use App\Domains\Stores\DTOs\PaginatedResult;
use App\Domains\Stores\DTOs\ProductDTO;
use DateTimeImmutable;

/**
 * Platform-neutral store boundary — all adapters (Shopify, WooCommerce) implement this port.
 * Returns DTOs only; never Eloquent models or platform-specific shapes.
 */
interface StorePort
{
    /** Verify stored credentials against the live platform API. */
    public function verifyCredentials(): bool;

    /** Cursor-paginated order list from the live platform. */
    public function listOrders(OrderQuery $query): PaginatedResult;

    public function getOrder(string $externalId): OrderDTO;

    /**
     * Patch order fields: status, note, tags, shipping_address, tracking_number, tracking_company (ADR 044).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateOrder(string $externalId, array $attributes): OrderDTO;

    public function fulfillOrder(string $externalId, ?string $trackingNumber = null): OrderDTO;

    public function refundOrder(string $externalId, ?int $amountMinor = null): OrderDTO;

    /** @param  array<int, string>  $tags */
    public function tagOrder(string $externalId, array $tags): OrderDTO;

    public function cancelOrder(string $externalId, ?string $reason = null): OrderDTO;

    public function listProducts(?string $search = null, int $limit = 25, ?string $cursor = null): PaginatedResult;

    public function getProduct(string $externalId): ProductDTO;

    /**
     * Create a product. Attributes: title, description, status, images => ProductImageInput[] (ADR 040).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createProduct(array $attributes): ProductDTO;

    /**
     * Update a product. Attributes: title, description, status, images => ProductImageInput[] (ADR 040).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateProduct(string $externalId, array $attributes): ProductDTO;

    public function deleteProduct(string $externalId): void;

    public function updateInventory(string $variantExternalId, int $quantity): void;

    public function listCustomers(?string $search = null, int $limit = 25, ?string $cursor = null): PaginatedResult;

    public function getCustomer(string $externalId): CustomerDTO;

    /** @param  array<int, string>  $tags */
    public function tagCustomer(string $externalId, array $tags): CustomerDTO;

    public function getMetrics(DateTimeImmutable $from, DateTimeImmutable $to): MetricDTO;

    /** Full mirror sync — orders, products, customers (ADR 016). */
    public function syncAll(): void;
}
