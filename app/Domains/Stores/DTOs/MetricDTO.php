<?php

declare(strict_types=1);

namespace App\Domains\Stores\DTOs;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final readonly class MetricDTO
{
    public function __construct(
        public int $revenueMinor,
        public int $ordersCount,
        public int $averageOrderValueMinor,
        public int $newCustomers,
        public int $returningCustomers,
        public int $unfulfilledOrders,
        public int $lowStockProducts,
        public string $currency,
    ) {}
}
