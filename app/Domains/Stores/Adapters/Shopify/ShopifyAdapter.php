<?php

declare(strict_types=1);

namespace App\Domains\Stores\Adapters\Shopify;

use App\Domains\Stores\Contracts\StorePort;
use App\Domains\Stores\DTOs\CustomerDTO;
use App\Domains\Stores\DTOs\MetricDTO;
use App\Domains\Stores\DTOs\OrderDTO;
use App\Domains\Stores\DTOs\OrderQuery;
use App\Domains\Stores\DTOs\PaginatedResult;
use App\Domains\Stores\DTOs\ProductDTO;
use App\Domains\Stores\DTOs\ProductImageInput;
use App\Domains\Stores\Exceptions\InvalidCredentialsException;
use App\Domains\Stores\Exceptions\ResourceNotFoundException;
use App\Domains\Stores\Exceptions\StoreApiException;
use App\Domains\Stores\Jobs\IncrementalSyncJob;
use DateTimeImmutable;

final class ShopifyAdapter implements StorePort
{
    public function __construct(
        private readonly ShopifyClient $client,
        private readonly ?string $connectionId = null,
        private readonly ShopifyNormalizer $normalizer = new ShopifyNormalizer,
    ) {}

    public function verifyCredentials(): bool
    {
        try {
            $data = $this->client->graphql('query { shop { name } }');

            return isset($data['shop']['name']);
        } catch (InvalidCredentialsException) {
            return false;
        }
    }

    public function listOrders(OrderQuery $query): PaginatedResult
    {
        $gql = <<<'GQL'
        query($first: Int!, $after: String, $q: String) {
          orders(first: $first, after: $after, query: $q) {
            edges {
              cursor
              node {
                id name displayFinancialStatus displayFulfillmentStatus
                totalPriceSet { shopMoney { amount currencyCode } }
                customer { id }
                createdAt
                lineItems(first: 50) {
                  edges {
                    node {
                      id title quantity
                      originalUnitPriceSet { shopMoney { amount currencyCode } }
                    }
                  }
                }
              }
            }
            pageInfo { hasNextPage endCursor }
          }
        }
        GQL;

        $data = $this->client->graphql($gql, [
            'first' => $query->limit,
            'after' => $query->cursor,
            'q' => $this->buildOrderSearchQuery($query),
        ]);

        /** @var array<int, array{node: array<string, mixed>}> $edges */
        $edges = $data['orders']['edges'] ?? [];

        return new PaginatedResult(
            items: array_map(fn (array $edge) => $this->normalizer->toOrderDTO($edge['node']), $edges),
            nextCursor: isset($data['orders']['pageInfo']['endCursor'])
                ? (string) $data['orders']['pageInfo']['endCursor']
                : null,
            hasMore: (bool) ($data['orders']['pageInfo']['hasNextPage'] ?? false),
        );
    }

    public function getOrder(string $externalId): OrderDTO
    {
        $gql = <<<'GQL'
        query($id: ID!) {
          order(id: $id) {
            id name displayFinancialStatus displayFulfillmentStatus
            totalPriceSet { shopMoney { amount currencyCode } }
            customer { id }
            createdAt
            lineItems(first: 50) {
              edges {
                node {
                  id title quantity
                  originalUnitPriceSet { shopMoney { amount currencyCode } }
                }
              }
            }
          }
        }
        GQL;

        $data = $this->client->graphql($gql, [
            'id' => Gid::ensureGid($externalId, 'Order'),
        ]);

        if (empty($data['order'])) {
            throw new ResourceNotFoundException('Order not found.');
        }

        /** @var array<string, mixed> $order */
        $order = $data['order'];

        return $this->normalizer->toOrderDTO($order);
    }

    public function updateOrder(string $externalId, array $attributes): OrderDTO
    {
        $input = ['id' => Gid::ensureGid($externalId, 'Order')];

        if (array_key_exists('note', $attributes)) {
            $input['note'] = $attributes['note'];
        }

        if (array_key_exists('tags', $attributes)) {
            $input['tags'] = is_array($attributes['tags'])
                ? implode(', ', $attributes['tags'])
                : $attributes['tags'];
        }

        if (isset($attributes['tracking_number'])) {
            $input['shippingTrackingNumber'] = $attributes['tracking_number'];
        }

        if (isset($attributes['tracking_company'])) {
            $input['shippingTrackingCompany'] = $attributes['tracking_company'];
        }

        if (isset($attributes['shipping_address']) && is_array($attributes['shipping_address'])) {
            $input['shippingAddress'] = $this->mapShippingAddress($attributes['shipping_address']);
        }

        $gql = <<<'GQL'
        mutation($input: OrderInput!) {
          orderUpdate(input: $input) {
            order {
              id name displayFinancialStatus displayFulfillmentStatus note tags
              totalPriceSet { shopMoney { amount currencyCode } }
              customer { id }
              createdAt
              lineItems(first: 50) {
                edges {
                  node {
                    id title quantity
                    originalUnitPriceSet { shopMoney { amount currencyCode } }
                  }
                }
              }
            }
            userErrors { field message }
          }
        }
        GQL;

        $data = $this->client->graphql($gql, ['input' => $input]);
        $this->guardUserErrors($data['orderUpdate']['userErrors'] ?? []);

        /** @var array<string, mixed> $order */
        $order = $data['orderUpdate']['order'];

        return $this->normalizer->toOrderDTO($order);
    }

    public function fulfillOrder(string $externalId, ?string $trackingNumber = null): OrderDTO
    {
        $orderGid = Gid::ensureGid($externalId, 'Order');

        $lookup = $this->client->graphql(<<<'GQL'
        query($id: ID!) {
          order(id: $id) {
            fulfillmentOrders(first: 1) {
              edges { node { id } }
            }
          }
        }
        GQL, ['id' => $orderGid]);

        $fulfillmentOrderId = $lookup['order']['fulfillmentOrders']['edges'][0]['node']['id'] ?? null;

        if (! is_string($fulfillmentOrderId) || $fulfillmentOrderId === '') {
            throw new StoreApiException('No open fulfillment order found for this order.');
        }

        $gql = <<<'GQL'
        mutation($fulfillment: FulfillmentV2Input!) {
          fulfillmentCreateV2(fulfillment: $fulfillment) {
            fulfillment { id }
            userErrors { field message }
          }
        }
        GQL;

        $fulfillment = [
            'lineItemsByFulfillmentOrder' => [
                ['fulfillmentOrderId' => $fulfillmentOrderId],
            ],
        ];

        if ($trackingNumber !== null) {
            $fulfillment['trackingInfo'] = ['number' => $trackingNumber];
        }

        $data = $this->client->graphql($gql, ['fulfillment' => $fulfillment]);
        $this->guardUserErrors($data['fulfillmentCreateV2']['userErrors'] ?? []);

        return $this->getOrder($externalId);
    }

    public function refundOrder(string $externalId, ?int $amountMinor = null): OrderDTO
    {
        $gql = <<<'GQL'
        mutation($input: RefundInput!) {
          refundCreate(input: $input) {
            refund { id }
            userErrors { field message }
          }
        }
        GQL;

        $input = [
            'orderId' => Gid::ensureGid($externalId, 'Order'),
        ];

        if ($amountMinor !== null) {
            $input['transactions'] = [
                [
                    'orderId' => Gid::ensureGid($externalId, 'Order'),
                    'amount' => (string) ($amountMinor / 100),
                    'kind' => 'REFUND',
                    'gateway' => 'manual',
                ],
            ];
        }

        $data = $this->client->graphql($gql, ['input' => $input]);
        $this->guardUserErrors($data['refundCreate']['userErrors'] ?? []);

        return $this->getOrder($externalId);
    }

    public function tagOrder(string $externalId, array $tags): OrderDTO
    {
        return $this->updateOrder($externalId, ['tags' => $tags]);
    }

    public function cancelOrder(string $externalId, ?string $reason = null): OrderDTO
    {
        $gql = <<<'GQL'
        mutation($orderId: ID!, $reason: OrderCancelReason) {
          orderCancel(orderId: $orderId, reason: $reason) {
            order {
              id name displayFinancialStatus displayFulfillmentStatus
              totalPriceSet { shopMoney { amount currencyCode } }
              customer { id }
              createdAt
              lineItems(first: 50) {
                edges {
                  node {
                    id title quantity
                    originalUnitPriceSet { shopMoney { amount currencyCode } }
                  }
                }
              }
            }
            userErrors { field message }
          }
        }
        GQL;

        $data = $this->client->graphql($gql, [
            'orderId' => Gid::ensureGid($externalId, 'Order'),
            'reason' => $reason !== null ? strtoupper($reason) : null,
        ]);
        $this->guardUserErrors($data['orderCancel']['userErrors'] ?? []);

        /** @var array<string, mixed> $order */
        $order = $data['orderCancel']['order'];

        return $this->normalizer->toOrderDTO($order);
    }

    public function listProducts(?string $search = null, int $limit = 25, ?string $cursor = null): PaginatedResult
    {
        $gql = <<<'GQL'
        query($first: Int!, $after: String, $q: String) {
          products(first: $first, after: $after, query: $q) {
            edges {
              cursor
              node {
                id title descriptionHtml status handle
                variants(first: 25) {
                  edges {
                    node {
                      id sku title price inventoryQuantity
                    }
                  }
                }
                media(first: 10) {
                  edges {
                    node {
                      preview { image { url } }
                    }
                  }
                }
              }
            }
            pageInfo { hasNextPage endCursor }
          }
        }
        GQL;

        $data = $this->client->graphql($gql, [
            'first' => $limit,
            'after' => $cursor,
            'q' => $search,
        ]);

        /** @var array<int, array{node: array<string, mixed>}> $edges */
        $edges = $data['products']['edges'] ?? [];

        return new PaginatedResult(
            items: array_map(fn (array $edge) => $this->normalizer->toProductDTO($edge['node']), $edges),
            nextCursor: isset($data['products']['pageInfo']['endCursor'])
                ? (string) $data['products']['pageInfo']['endCursor']
                : null,
            hasMore: (bool) ($data['products']['pageInfo']['hasNextPage'] ?? false),
        );
    }

    public function getProduct(string $externalId): ProductDTO
    {
        $gql = <<<'GQL'
        query($id: ID!) {
          product(id: $id) {
            id title descriptionHtml status handle
            variants(first: 25) {
              edges {
                node {
                  id sku title price inventoryQuantity
                }
              }
            }
            media(first: 10) {
              edges {
                node {
                  preview { image { url } }
                }
              }
            }
          }
        }
        GQL;

        $data = $this->client->graphql($gql, [
            'id' => Gid::ensureGid($externalId, 'Product'),
        ]);

        if (empty($data['product'])) {
            throw new ResourceNotFoundException('Product not found.');
        }

        /** @var array<string, mixed> $product */
        $product = $data['product'];

        return $this->normalizer->toProductDTO($product);
    }

    public function createProduct(array $attributes): ProductDTO
    {
        $input = $this->mapProductInput($attributes);

        $gql = <<<'GQL'
        mutation($input: ProductInput!) {
          productCreate(input: $input) {
            product {
              id title descriptionHtml status handle
              variants(first: 25) {
                edges {
                  node {
                    id sku title price inventoryQuantity
                  }
                }
              }
              media(first: 10) {
                edges {
                  node {
                    preview { image { url } }
                  }
                }
              }
            }
            userErrors { field message }
          }
        }
        GQL;

        $data = $this->client->graphql($gql, ['input' => $input]);
        $this->guardUserErrors($data['productCreate']['userErrors'] ?? []);

        /** @var array<string, mixed> $product */
        $product = $data['productCreate']['product'];

        if (isset($attributes['images']) && is_array($attributes['images']) && $attributes['images'] !== []) {
            $this->uploadProductImages((string) $product['id'], $attributes['images']);

            return $this->getProduct((string) $product['id']);
        }

        return $this->normalizer->toProductDTO($product);
    }

    public function updateProduct(string $externalId, array $attributes): ProductDTO
    {
        if (isset($attributes['images']) && is_array($attributes['images']) && $attributes['images'] !== []) {
            $this->uploadProductImages($externalId, $attributes['images']);
            unset($attributes['images']);
        }

        if ($attributes === []) {
            return $this->getProduct($externalId);
        }

        $input = array_merge(
            ['id' => Gid::ensureGid($externalId, 'Product')],
            $this->mapProductInput($attributes),
        );

        $gql = <<<'GQL'
        mutation($input: ProductInput!) {
          productUpdate(input: $input) {
            product {
              id title descriptionHtml status handle
              variants(first: 25) {
                edges {
                  node {
                    id sku title price inventoryQuantity
                  }
                }
              }
              media(first: 10) {
                edges {
                  node {
                    preview { image { url } }
                  }
                }
              }
            }
            userErrors { field message }
          }
        }
        GQL;

        $data = $this->client->graphql($gql, ['input' => $input]);
        $this->guardUserErrors($data['productUpdate']['userErrors'] ?? []);

        /** @var array<string, mixed> $product */
        $product = $data['productUpdate']['product'];

        return $this->normalizer->toProductDTO($product);
    }

    public function deleteProduct(string $externalId): void
    {
        $gql = <<<'GQL'
        mutation($input: ProductDeleteInput!) {
          productDelete(input: $input) {
            deletedProductId
            userErrors { field message }
          }
        }
        GQL;

        $data = $this->client->graphql($gql, [
            'input' => ['id' => Gid::ensureGid($externalId, 'Product')],
        ]);
        $this->guardUserErrors($data['productDelete']['userErrors'] ?? []);
    }

    public function updateInventory(string $variantExternalId, int $quantity): void
    {
        $gql = <<<'GQL'
        mutation($input: InventorySetQuantitiesInput!) {
          inventorySetQuantities(input: $input) {
            userErrors { field message }
          }
        }
        GQL;

        $data = $this->client->graphql($gql, [
            'input' => [
                'reason' => 'correction',
                'name' => 'available',
                'quantities' => [
                    [
                        'inventoryItemId' => Gid::ensureGid($variantExternalId, 'InventoryItem'),
                        'locationId' => 'gid://shopify/Location/1',
                        'quantity' => $quantity,
                    ],
                ],
            ],
        ]);
        $this->guardUserErrors($data['inventorySetQuantities']['userErrors'] ?? []);
    }

    public function listCustomers(?string $search = null, int $limit = 25, ?string $cursor = null): PaginatedResult
    {
        $gql = <<<'GQL'
        query($first: Int!, $after: String, $q: String) {
          customers(first: $first, after: $after, query: $q) {
            edges {
              cursor
              node {
                id email firstName lastName numberOfOrders
                amountSpent { amount currencyCode }
              }
            }
            pageInfo { hasNextPage endCursor }
          }
        }
        GQL;

        $data = $this->client->graphql($gql, [
            'first' => $limit,
            'after' => $cursor,
            'q' => $search,
        ]);

        /** @var array<int, array{node: array<string, mixed>}> $edges */
        $edges = $data['customers']['edges'] ?? [];

        return new PaginatedResult(
            items: array_map(fn (array $edge) => $this->normalizer->toCustomerDTO($edge['node']), $edges),
            nextCursor: isset($data['customers']['pageInfo']['endCursor'])
                ? (string) $data['customers']['pageInfo']['endCursor']
                : null,
            hasMore: (bool) ($data['customers']['pageInfo']['hasNextPage'] ?? false),
        );
    }

    public function getCustomer(string $externalId): CustomerDTO
    {
        $gql = <<<'GQL'
        query($id: ID!) {
          customer(id: $id) {
            id email firstName lastName numberOfOrders
            amountSpent { amount currencyCode }
          }
        }
        GQL;

        $data = $this->client->graphql($gql, [
            'id' => Gid::ensureGid($externalId, 'Customer'),
        ]);

        if (empty($data['customer'])) {
            throw new ResourceNotFoundException('Customer not found.');
        }

        /** @var array<string, mixed> $customer */
        $customer = $data['customer'];

        return $this->normalizer->toCustomerDTO($customer);
    }

    public function tagCustomer(string $externalId, array $tags): CustomerDTO
    {
        $gql = <<<'GQL'
        mutation($input: CustomerInput!) {
          customerUpdate(input: $input) {
            customer {
              id email firstName lastName numberOfOrders
              amountSpent { amount currencyCode }
            }
            userErrors { field message }
          }
        }
        GQL;

        $data = $this->client->graphql($gql, [
            'input' => [
                'id' => Gid::ensureGid($externalId, 'Customer'),
                'tags' => $tags,
            ],
        ]);
        $this->guardUserErrors($data['customerUpdate']['userErrors'] ?? []);

        /** @var array<string, mixed> $customer */
        $customer = $data['customerUpdate']['customer'];

        return $this->normalizer->toCustomerDTO($customer);
    }

    public function getMetrics(DateTimeImmutable $from, DateTimeImmutable $to): MetricDTO
    {
        $query = sprintf(
            'created_at:>=%s created_at:<=%s',
            $from->format('Y-m-d'),
            $to->format('Y-m-d'),
        );

        $gql = <<<'GQL'
        query($q: String!) {
          orders(first: 250, query: $q) {
            edges {
              node {
                id
                displayFulfillmentStatus
                totalPriceSet { shopMoney { amount currencyCode } }
                customer { id }
              }
            }
          }
        }
        GQL;

        $data = $this->client->graphql($gql, ['q' => $query]);

        /** @var array<int, array{node: array<string, mixed>}> $edges */
        $edges = $data['orders']['edges'] ?? [];

        $revenueMinor = 0;
        $currency = 'EUR';
        $unfulfilled = 0;
        $customerIds = [];

        foreach ($edges as $edge) {
            /** @var array<string, mixed> $node */
            $node = $edge['node'];
            $orderCurrency = $node['totalPriceSet']['shopMoney']['currencyCode'] ?? 'EUR';

            if (is_string($orderCurrency)) {
                $currency = $orderCurrency;
            }

            $revenueMinor += Money::toMinor(
                $node['totalPriceSet']['shopMoney']['amount'] ?? null,
                $orderCurrency,
            );

            if (($node['displayFulfillmentStatus'] ?? null) === 'UNFULFILLED') {
                $unfulfilled++;
            }

            if (isset($node['customer']['id'])) {
                $customerIds[] = (string) $node['customer']['id'];
            }
        }

        $ordersCount = count($edges);
        $uniqueCustomers = count(array_unique($customerIds));

        return new MetricDTO(
            revenueMinor: $revenueMinor,
            ordersCount: $ordersCount,
            averageOrderValueMinor: $ordersCount > 0 ? (int) round($revenueMinor / $ordersCount) : 0,
            newCustomers: $uniqueCustomers,
            returningCustomers: max(0, $ordersCount - $uniqueCustomers),
            unfulfilledOrders: $unfulfilled,
            lowStockProducts: 0,
            currency: $currency,
        );
    }

    public function syncAll(): void
    {
        if ($this->connectionId === null) {
            throw new StoreApiException('Store connection is not bound to this adapter.');
        }

        IncrementalSyncJob::dispatch($this->connectionId);
    }

    /**
     * @param  array<int, ProductImageInput|array<string, string>>  $images
     */
    private function uploadProductImages(string $productExternalId, array $images): void
    {
        foreach ($images as $image) {
            if (is_array($image)) {
                $image = new ProductImageInput(
                    localPath: $image['localPath'],
                    mimeType: $image['mimeType'],
                    filename: $image['filename'],
                );
            }

            $staged = $this->client->graphql($this->stagedUploadCreateMutation(), [
                'input' => [[
                    'filename' => $image->filename,
                    'mimeType' => $image->mimeType,
                    'httpMethod' => 'POST',
                    'resource' => 'PRODUCT_IMAGE',
                ]],
            ]);

            $this->guardUserErrors($staged['stagedUploadsCreate']['userErrors'] ?? []);

            /** @var array<string, mixed> $target */
            $target = $staged['stagedUploadsCreate']['stagedTargets'][0] ?? [];

            if ($target === []) {
                throw new StoreApiException('Shopify did not return a staged upload target.');
            }

            $this->client->uploadToStagedTarget($target, $image->localPath);

            $media = $this->client->graphql($this->productCreateMediaMutation(), [
                'productId' => Gid::ensureGid($productExternalId, 'Product'),
                'media' => [[
                    'originalSource' => $target['resourceUrl'],
                    'mediaContentType' => 'IMAGE',
                ]],
            ]);

            $this->guardUserErrors($media['productCreateMedia']['mediaUserErrors'] ?? []);
        }
    }

    private function stagedUploadCreateMutation(): string
    {
        return <<<'GQL'
        mutation($input: [StagedUploadInput!]!) {
          stagedUploadsCreate(input: $input) {
            stagedTargets {
              url
              resourceUrl
              parameters { name value }
            }
            userErrors { field message }
          }
        }
        GQL;
    }

    private function productCreateMediaMutation(): string
    {
        return <<<'GQL'
        mutation($productId: ID!, $media: [CreateMediaInput!]!) {
          productCreateMedia(productId: $productId, media: $media) {
            media { id status }
            mediaUserErrors { field message }
          }
        }
        GQL;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function mapProductInput(array $attributes): array
    {
        $input = [];

        if (isset($attributes['title'])) {
            $input['title'] = $attributes['title'];
        }

        if (isset($attributes['description'])) {
            $input['descriptionHtml'] = $attributes['description'];
        }

        if (isset($attributes['status'])) {
            $input['status'] = strtoupper((string) $attributes['status']);
        }

        return $input;
    }

    /**
     * @param  array<string, mixed>  $address
     * @return array<string, mixed>
     */
    private function mapShippingAddress(array $address): array
    {
        return array_filter([
            'address1' => $address['address1'] ?? $address['line1'] ?? null,
            'address2' => $address['address2'] ?? $address['line2'] ?? null,
            'city' => $address['city'] ?? null,
            'province' => $address['province'] ?? $address['state'] ?? null,
            'country' => $address['country'] ?? null,
            'zip' => $address['zip'] ?? $address['postal_code'] ?? null,
            'firstName' => $address['first_name'] ?? $address['firstName'] ?? null,
            'lastName' => $address['last_name'] ?? $address['lastName'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function buildOrderSearchQuery(OrderQuery $query): ?string
    {
        $parts = [];

        if ($query->fulfillmentStatus !== null) {
            $parts[] = 'fulfillment_status:'.strtolower($query->fulfillmentStatus);
        }

        if ($query->financialStatus !== null) {
            $parts[] = 'financial_status:'.strtolower($query->financialStatus);
        }

        if ($query->placedAfter !== null) {
            $parts[] = 'created_at:>='.$query->placedAfter->format('Y-m-d');
        }

        if ($query->updatedSince !== null) {
            $parts[] = 'updated_at:>='.$query->updatedSince->format('Y-m-d\TH:i:s\Z');
        }

        if ($query->search !== null) {
            $parts[] = $query->search;
        }

        return $parts === [] ? null : implode(' ', $parts);
    }

    /**
     * @param  array<int, array<string, mixed>>  $errors
     */
    private function guardUserErrors(array $errors): void
    {
        if ($errors !== []) {
            throw new StoreApiException($errors[0]['message'] ?? 'Shopify rejected the write');
        }
    }
}
