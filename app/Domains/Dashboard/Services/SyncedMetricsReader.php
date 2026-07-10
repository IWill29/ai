<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\Services;

use App\Domains\Dashboard\Contracts\MetricsReader;
use App\Domains\Stores\DTOs\MetricDTO;
use BadMethodCallException;
use DateTimeImmutable;

/**
 * Placeholder until Phase 7 (mirror aggregation queries).
 */
final class SyncedMetricsReader implements MetricsReader
{
    public function forStores(array $storeConnectionIds, DateTimeImmutable $from, DateTimeImmutable $to): MetricDTO
    {
        throw new BadMethodCallException('MetricsReader not implemented until Phase 7.');
    }
}
