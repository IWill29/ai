<?php

declare(strict_types=1);

namespace Tests\Unit\Sync;

use App\Domains\Stores\Jobs\IncrementalSyncJob;
use App\Domains\Stores\Jobs\InitialBulkSyncJob;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class UniqueSyncJobsTest extends TestCase
{
    public function test_initial_and_incremental_sync_jobs_share_unique_id_per_store(): void
    {
        $bulk = new InitialBulkSyncJob('store-1');
        $incremental = new IncrementalSyncJob('store-1');

        $this->assertSame('sync:store-1', $bulk->uniqueId());
        $this->assertSame('sync:store-1', $incremental->uniqueId());
    }

    public function test_dispatching_duplicate_sync_jobs_only_queues_one_with_fake(): void
    {
        Queue::fake();

        InitialBulkSyncJob::dispatch('store-1');
        InitialBulkSyncJob::dispatch('store-1');

        Queue::assertPushed(InitialBulkSyncJob::class, 1);
    }
}
