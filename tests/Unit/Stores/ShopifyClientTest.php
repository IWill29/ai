<?php

declare(strict_types=1);

namespace Tests\Unit\Stores;

use App\Domains\Stores\Adapters\Shopify\ShopifyClient;
use App\Domains\Stores\Exceptions\RateLimitException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShopifyClientTest extends TestCase
{
    public function test_maps_throttled_response_to_rate_limit_exception(): void
    {
        Http::fake([
            '*/graphql.json' => Http::response([
                'errors' => [
                    ['extensions' => ['code' => 'THROTTLED'], 'message' => 'Throttled'],
                ],
            ]),
        ]);

        $client = new ShopifyClient('demo.myshopify.com', 'shpat_test_token_1234567890');

        $this->expectException(RateLimitException::class);

        $client->graphql('query { shop { name } }');
    }
}
