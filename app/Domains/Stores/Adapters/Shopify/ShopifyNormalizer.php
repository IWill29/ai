<?php

declare(strict_types=1);

namespace App\Domains\Stores\Adapters\Shopify;

use App\Domains\Stores\DTOs\CustomerDTO;
use App\Domains\Stores\DTOs\LineItemDTO;
use App\Domains\Stores\DTOs\OrderDTO;
use App\Domains\Stores\DTOs\ProductDTO;
use App\Domains\Stores\DTOs\VariantDTO;

final class ShopifyNormalizer
{
    /**
     * @param  array<string, mixed>  $node
     */
    public function toOrderDTO(array $node): OrderDTO
    {
        return new OrderDTO(
            externalId: Gid::idFromGid((string) ($node['id'] ?? '')),
            orderNumber: isset($node['name']) ? (string) $node['name'] : null,
            financialStatus: isset($node['displayFinancialStatus']) ? (string) $node['displayFinancialStatus'] : null,
            fulfillmentStatus: isset($node['displayFulfillmentStatus']) ? (string) $node['displayFulfillmentStatus'] : null,
            totalPriceMinor: Money::toMinor(
                $node['totalPriceSet']['shopMoney']['amount'] ?? null,
                $node['totalPriceSet']['shopMoney']['currencyCode'] ?? null,
            ),
            currency: isset($node['totalPriceSet']['shopMoney']['currencyCode'])
                ? (string) $node['totalPriceSet']['shopMoney']['currencyCode']
                : null,
            customerExternalId: isset($node['customer']['id'])
                ? Gid::idFromGid((string) $node['customer']['id'])
                : null,
            lineItems: $this->normalizeLineItems($node),
            placedAt: isset($node['createdAt'])
                ? new \DateTimeImmutable((string) $node['createdAt'])
                : null,
        );
    }

    /**
     * @param  array<string, mixed>  $node
     */
    public function toProductDTO(array $node): ProductDTO
    {
        return new ProductDTO(
            externalId: Gid::idFromGid((string) ($node['id'] ?? '')),
            title: (string) ($node['title'] ?? ''),
            description: isset($node['descriptionHtml']) ? (string) $node['descriptionHtml'] : null,
            status: isset($node['status']) ? (string) $node['status'] : null,
            handle: isset($node['handle']) ? (string) $node['handle'] : null,
            variants: $this->normalizeVariants($node),
            imageUrls: $this->normalizeImageUrls($node),
        );
    }

    /**
     * @param  array<string, mixed>  $node
     */
    public function toCustomerDTO(array $node): CustomerDTO
    {
        $name = trim(implode(' ', array_filter([
            $node['firstName'] ?? null,
            $node['lastName'] ?? null,
        ])));

        return new CustomerDTO(
            externalId: Gid::idFromGid((string) ($node['id'] ?? '')),
            email: isset($node['email']) ? (string) $node['email'] : null,
            name: $name !== '' ? $name : null,
            ordersCount: (int) ($node['numberOfOrders'] ?? 0),
            totalSpentMinor: Money::toMinor(
                $node['amountSpent']['amount'] ?? null,
                $node['amountSpent']['currencyCode'] ?? null,
            ),
            currency: isset($node['amountSpent']['currencyCode'])
                ? (string) $node['amountSpent']['currencyCode']
                : null,
        );
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array<int, LineItemDTO>
     */
    private function normalizeLineItems(array $node): array
    {
        /** @var array<int, array{node?: array<string, mixed>}> $edges */
        $edges = $node['lineItems']['edges'] ?? [];

        return array_map(function (array $edge): LineItemDTO {
            /** @var array<string, mixed> $item */
            $item = $edge['node'] ?? [];

            return new LineItemDTO(
                externalId: Gid::idFromGid((string) ($item['id'] ?? '')),
                title: (string) ($item['title'] ?? ''),
                quantity: (int) ($item['quantity'] ?? 0),
                priceMinor: Money::toMinor(
                    $item['originalUnitPriceSet']['shopMoney']['amount'] ?? null,
                    $item['originalUnitPriceSet']['shopMoney']['currencyCode'] ?? null,
                ),
                currency: isset($item['originalUnitPriceSet']['shopMoney']['currencyCode'])
                    ? (string) $item['originalUnitPriceSet']['shopMoney']['currencyCode']
                    : null,
            );
        }, $edges);
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array<int, VariantDTO>
     */
    private function normalizeVariants(array $node): array
    {
        /** @var array<int, array{node?: array<string, mixed>}> $edges */
        $edges = $node['variants']['edges'] ?? [];

        return array_map(function (array $edge) use ($node): VariantDTO {
            /** @var array<string, mixed> $variant */
            $variant = $edge['node'] ?? [];

            return new VariantDTO(
                externalId: Gid::idFromGid((string) ($variant['id'] ?? '')),
                sku: isset($variant['sku']) ? (string) $variant['sku'] : null,
                title: isset($variant['title']) ? (string) $variant['title'] : null,
                priceMinor: isset($variant['price'])
                    ? Money::toMinor((string) $variant['price'], $node['currencyCode'] ?? 'USD')
                    : null,
                currency: isset($node['currencyCode']) ? (string) $node['currencyCode'] : null,
                inventoryQuantity: isset($variant['inventoryQuantity'])
                    ? (int) $variant['inventoryQuantity']
                    : null,
            );
        }, $edges);
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array<int, string>
     */
    private function normalizeImageUrls(array $node): array
    {
        /** @var array<int, array{node?: array<string, mixed>}> $edges */
        $edges = $node['media']['edges'] ?? $node['images']['edges'] ?? [];

        $urls = [];

        foreach ($edges as $edge) {
            $url = $edge['node']['preview']['image']['url']
                ?? $edge['node']['url']
                ?? null;

            if (is_string($url) && $url !== '') {
                $urls[] = $url;
            }
        }

        return $urls;
    }
}
