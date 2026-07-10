<?php

declare(strict_types=1);

namespace App\Domains\Stores\Adapters\Shopify;

use App\Domains\Stores\Exceptions\StoreApiException;
use Illuminate\Support\Facades\Cache;

final class ShopifyCircuitBreaker
{
    public function __construct(
        private readonly ?string $connectionId,
    ) {}

    public function assertClosed(): void
    {
        if ($this->connectionId === null) {
            return;
        }

        $key = $this->cacheKey();
        $failures = (int) Cache::get($key, 0);
        $threshold = (int) config('shopify.circuit_breaker_threshold', 5);

        if ($failures >= $threshold) {
            throw new StoreApiException('Store temporarily unavailable (circuit open).');
        }
    }

    public function recordFailure(): void
    {
        if ($this->connectionId === null) {
            return;
        }

        $key = $this->cacheKey();
        $ttl = (int) config('shopify.circuit_breaker_ttl_seconds', 60);

        if (! Cache::has($key)) {
            Cache::put($key, 1, $ttl);

            return;
        }

        Cache::increment($key);
    }

    public function recordSuccess(): void
    {
        if ($this->connectionId === null) {
            return;
        }

        Cache::forget($this->cacheKey());
    }

    private function cacheKey(): string
    {
        return "shopify:breaker:{$this->connectionId}";
    }
}
