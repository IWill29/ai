<?php

declare(strict_types=1);

namespace App\Domains\Stores\Services;

use App\Domains\Stores\Contracts\StorePort;
use App\Domains\Stores\DTOs\CustomerDTO;
use App\Domains\Stores\DTOs\MetricDTO;
use App\Domains\Stores\DTOs\OrderDTO;
use App\Domains\Stores\DTOs\OrderQuery;
use App\Domains\Stores\DTOs\PaginatedResult;
use App\Domains\Stores\DTOs\ProductDTO;
use BadMethodCallException;
use DateTimeImmutable;

/**
 * Placeholder StorePort until Phase 5 (ShopifyAdapter).
 */
final class StubStorePort implements StorePort
{
    private function notImplemented(): never
    {
        throw new BadMethodCallException('StorePort not implemented until Phase 5.');
    }

    public function verifyCredentials(): bool
    {
        $this->notImplemented();
    }

    public function listOrders(OrderQuery $query): PaginatedResult
    {
        $this->notImplemented();
    }

    public function getOrder(string $externalId): OrderDTO
    {
        $this->notImplemented();
    }

    public function updateOrder(string $externalId, array $attributes): OrderDTO
    {
        $this->notImplemented();
    }

    public function fulfillOrder(string $externalId, ?string $trackingNumber = null): OrderDTO
    {
        $this->notImplemented();
    }

    public function refundOrder(string $externalId, ?int $amountMinor = null): OrderDTO
    {
        $this->notImplemented();
    }

    public function tagOrder(string $externalId, array $tags): OrderDTO
    {
        $this->notImplemented();
    }

    public function cancelOrder(string $externalId, ?string $reason = null): OrderDTO
    {
        $this->notImplemented();
    }

    public function listProducts(?string $search = null, int $limit = 25, ?string $cursor = null): PaginatedResult
    {
        $this->notImplemented();
    }

    public function getProduct(string $externalId): ProductDTO
    {
        $this->notImplemented();
    }

    public function createProduct(array $attributes): ProductDTO
    {
        $this->notImplemented();
    }

    public function updateProduct(string $externalId, array $attributes): ProductDTO
    {
        $this->notImplemented();
    }

    public function deleteProduct(string $externalId): void
    {
        $this->notImplemented();
    }

    public function updateInventory(string $variantExternalId, int $quantity): void
    {
        $this->notImplemented();
    }

    public function listCustomers(?string $search = null, int $limit = 25, ?string $cursor = null): PaginatedResult
    {
        $this->notImplemented();
    }

    public function getCustomer(string $externalId): CustomerDTO
    {
        $this->notImplemented();
    }

    public function tagCustomer(string $externalId, array $tags): CustomerDTO
    {
        $this->notImplemented();
    }

    public function getMetrics(DateTimeImmutable $from, DateTimeImmutable $to): MetricDTO
    {
        $this->notImplemented();
    }

    public function syncAll(): void
    {
        $this->notImplemented();
    }
}
