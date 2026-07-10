<?php

declare(strict_types=1);

namespace App\Domains\Stores\Jobs;

use App\Domains\Stores\Models\StoreConnection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class RetryPendingWebhooksJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        StoreConnection::query()
            ->where('status', 'webhooks_pending')
            ->pluck('id')
            ->each(fn (string $connectionId) => RegisterShopifyWebhooksJob::dispatch($connectionId));
    }
}
