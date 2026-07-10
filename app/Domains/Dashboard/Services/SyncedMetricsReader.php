<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\Services;

use App\Domains\Dashboard\Contracts\MetricsReader;
use App\Domains\Shared\Concerns\DefersImplementation;
use App\Domains\Stores\DTOs\MetricDTO;
use DateTimeImmutable;

/**
 * Placeholder until Phase 7 (mirror aggregation queries).
 */
final class SyncedMetricsReader implements MetricsReader
{
    use DefersImplementation;

    public function forStores(array $storeConnectionIds, DateTimeImmutable $from, DateTimeImmutable $to): MetricDTO
    {
        $this->notImplemented('MetricsReader');
    }
}
