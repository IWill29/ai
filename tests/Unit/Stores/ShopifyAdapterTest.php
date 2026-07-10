<?php

declare(strict_types=1);

namespace Tests\Unit\Stores;

use App\Domains\Stores\Adapters\Shopify\ShopifyAdapter;
use App\Domains\Stores\Adapters\Shopify\ShopifyClient;
use App\Domains\Stores\DTOs\OrderQuery;
use App\Domains\Stores\Exceptions\StoreApiException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShopifyAdapterTest extends TestCase
{
    public function test_normalizes_orders_into_order_dto_with_minor_unit_totals(): void
    {
        Http::fake([
            '*/graphql.json' => Http::response([
                'data' => [
                    'orders' => [
                        'edges' => [
                            [
                                'cursor' => 'cursor1',
                                'node' => [
                                    'id' => 'gid://shopify/Order/12345',
                                    'name' => '#1001',
                                    'displayFinancialStatus' => 'PAID',
                                    'displayFulfillmentStatus' => 'UNFULFILLED',
                                    'totalPriceSet' => [
                                        'shopMoney' => [
                                            'amount' => '12.30',
                                            'currencyCode' => 'EUR',
                                        ],
                                    ],
                                    'customer' => ['id' => 'gid://shopify/Customer/1'],
                                    'createdAt' => '2026-07-10T10:00:00Z',
                                    'lineItems' => ['edges' => []],
                                ],
                            ],
                        ],
                        'pageInfo' => [
                            'hasNextPage' => false,
                            'endCursor' => 'cursor1',
                        ],
                    ],
                ],
            ]),
        ]);

        $adapter = new ShopifyAdapter(
            new ShopifyClient('demo.myshopify.com', 'shpat_test_token_1234567890'),
        );

        $result = $adapter->listOrders(new OrderQuery(limit: 10));

        $this->assertCount(1, $result->items);
        $this->assertSame('gid://shopify/Order/12345', $result->items[0]->externalId);
        $this->assertSame(1230, $result->items[0]->totalPriceMinor);
        $this->assertSame('EUR', $result->items[0]->currency);
    }

    public function test_update_product_checks_user_errors(): void
    {
        Http::fake([
            '*/graphql.json' => Http::sequence()
                ->push([
                    'data' => [
                        'productUpdate' => [
                            'product' => null,
                            'userErrors' => [
                                ['field' => ['title'], 'message' => 'Title is invalid'],
                            ],
                        ],
                    ],
                ]),
        ]);

        $adapter = new ShopifyAdapter(
            new ShopifyClient('demo.myshopify.com', 'shpat_test_token_1234567890'),
        );

        $this->expectException(StoreApiException::class);
        $this->expectExceptionMessage('Title is invalid');

        $adapter->updateProduct('gid://shopify/Product/1', ['title' => 'Bad']);
    }
}
