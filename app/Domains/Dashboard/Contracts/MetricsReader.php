<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\Contracts;

use App\Domains\Stores\DTOs\MetricDTO;
use DateTimeImmutable;

/**
 * Dashboard KPIs from the synced mirror — not live StorePort per page load.
 */
interface MetricsReader
{
    /**
     * @param  array<int, string>  $storeConnectionIds
     */
    public function forStores(array $storeConnectionIds, DateTimeImmutable $from, DateTimeImmutable $to): MetricDTO;
}
