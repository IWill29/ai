<?php

declare(strict_types=1);

namespace App\Domains\Stores\Jobs;

use App\Domains\Stores\Exceptions\InvalidCredentialsException;
use App\Domains\Stores\Exceptions\RateLimitException;
use App\Domains\Stores\Models\StoreConnection;
use App\Domains\Stores\Services\SyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class IncrementalSyncJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 300];

    public function __construct(
        public string $storeConnectionId,
        public string $mode = 'incremental',
    ) {}

    public function uniqueId(): string
    {
        return "sync:{$this->storeConnectionId}";
    }

    public function handle(SyncService $sync): void
    {
        $connection = StoreConnection::query()->findOrFail($this->storeConnectionId);

        try {
            $sync->runIncrementalSync($connection);
        } catch (RateLimitException $exception) {
            $this->release($exception->retryAfterSeconds ?? 30);
        } catch (InvalidCredentialsException) {
            // Connection status already updated by SyncService.
        }
    }
}
