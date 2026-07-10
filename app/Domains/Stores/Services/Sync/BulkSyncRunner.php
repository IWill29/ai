<?php

declare(strict_types=1);

namespace App\Domains\Stores\Services\Sync;

use App\Domains\Stores\Adapters\Shopify\ShopifyClient;
use App\Domains\Stores\Exceptions\StoreApiException;
use App\Domains\Stores\Models\StoreConnection;
use App\Domains\Stores\Services\SyncService;
use Illuminate\Support\Facades\Http;

final class BulkSyncRunner
{
    private const QUERIES = [
        'products' => <<<'GQL'
        {
          products {
            edges {
              node {
                id title descriptionHtml status handle createdAt updatedAt
                variants(first: 100) {
                  edges {
                    node {
                      id title sku price inventoryQuantity
                    }
                  }
                }
              }
            }
          }
        }
        GQL,
        'customers' => <<<'GQL'
        {
          customers {
            edges {
              node {
                id email firstName lastName numberOfOrders
                amountSpent { amount currencyCode }
                createdAt updatedAt
              }
            }
          }
        }
        GQL,
        'orders' => <<<'GQL'
        {
          orders {
            edges {
              node {
                id name displayFinancialStatus displayFulfillmentStatus
                totalPriceSet { shopMoney { amount currencyCode } }
                customer { id }
                createdAt updatedAt
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
          }
        }
        GQL,
    ];

    public function __construct(
        private readonly MirrorUpserter $upserter,
    ) {}

    public function run(ShopifyClient $client, StoreConnection $connection, SyncService $sync): void
    {
        foreach (self::QUERIES as $entity => $query) {
            $sync->setSyncProgress($connection, $entity, 'bulk');

            $data = $client->graphql(
                'mutation($q: String!) { bulkOperationRunQuery(query: $q) { bulkOperation { id status } userErrors { message } } }',
                ['q' => $query],
            );

            $userErrors = $data['bulkOperationRunQuery']['userErrors'] ?? [];

            if ($userErrors !== []) {
                throw new StoreApiException((string) ($userErrors[0]['message'] ?? 'Bulk operation rejected'));
            }

            $bulkId = $data['bulkOperationRunQuery']['bulkOperation']['id'] ?? null;

            if (! is_string($bulkId) || $bulkId === '') {
                throw new StoreApiException('Bulk operation did not return an ID.');
            }

            $url = $this->pollUntilComplete($client, $bulkId);
            $this->importJsonl($connection, $url, $entity);
        }
    }

    private function pollUntilComplete(ShopifyClient $client, string $bulkId): string
    {
        $maxAttempts = (int) config('shopify.bulk_poll_max_attempts', 120);
        $sleepSeconds = (int) config('shopify.bulk_poll_interval_seconds', 0);

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $data = $client->graphql(
                'query($id: ID!) { bulkOperation(id: $id) { status url errorCode } }',
                ['id' => $bulkId],
            );

            $operation = $data['bulkOperation'] ?? [];
            $status = $operation['status'] ?? null;

            if ($status === 'COMPLETED') {
                $url = $operation['url'] ?? null;

                if (! is_string($url) || $url === '') {
                    throw new StoreApiException('Bulk operation completed without a download URL.');
                }

                return $url;
            }

            if ($status === 'FAILED' || $status === 'CANCELED') {
                throw new StoreApiException("Bulk operation {$status}: ".($operation['errorCode'] ?? 'unknown'));
            }

            if ($sleepSeconds > 0) {
                sleep($sleepSeconds);
            }
        }

        throw new StoreApiException('Bulk operation timed out');
    }

    private function importJsonl(StoreConnection $connection, string $url, string $entity): void
    {
        $response = Http::timeout((int) config('shopify.timeout', 15))->get($url);

        if ($response->failed()) {
            throw new StoreApiException("Failed to download bulk JSONL: HTTP {$response->status()}");
        }

        foreach (explode("\n", trim($response->body())) as $line) {
            if ($line === '') {
                continue;
            }

            /** @var array<string, mixed> $node */
            $node = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
            $this->upserter->upsertFromBulkNode($connection, $entity, $node);
        }
    }
}
