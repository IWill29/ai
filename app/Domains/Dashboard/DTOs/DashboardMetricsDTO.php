<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\DTOs;

use DateTimeImmutable;
use JsonSerializable;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final readonly class DashboardMetricsDTO implements JsonSerializable
{
    public function __construct(
        public int $revenueMinor,
        public float $revenueChangePercent,
        public int $ordersCount,
        public float $ordersChangePercent,
        public int $averageOrderValueMinor,
        public int $newCustomers,
        public int $returningCustomers,
        public int $unfulfilledOrders,
        public int $lowStockProducts,
        public int $outOfStockProducts,
        public string $currency,
        public DateTimeImmutable $from,
        public DateTimeImmutable $to,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'revenueMinor' => $this->revenueMinor,
            'revenueChangePercent' => $this->revenueChangePercent,
            'ordersCount' => $this->ordersCount,
            'ordersChangePercent' => $this->ordersChangePercent,
            'averageOrderValueMinor' => $this->averageOrderValueMinor,
            'newCustomers' => $this->newCustomers,
            'returningCustomers' => $this->returningCustomers,
            'unfulfilledOrders' => $this->unfulfilledOrders,
            'lowStockProducts' => $this->lowStockProducts,
            'outOfStockProducts' => $this->outOfStockProducts,
            'currency' => $this->currency,
            'from' => $this->from->format(DATE_ATOM),
            'to' => $this->to->format(DATE_ATOM),
        ];
    }
}
