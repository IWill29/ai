<?php

declare(strict_types=1);

namespace App\Domains\Stores\Jobs;

use App\Domains\Stores\Models\StoreConnection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class NightlyReconcileJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        $connections = StoreConnection::query()
            ->whereIn('status', ['active', 'webhooks_pending'])
            ->get();

        foreach ($connections as $connection) {
            IncrementalSyncJob::dispatch($connection->id, 'reconcile');

            if ($connection->status === 'webhooks_pending') {
                RegisterShopifyWebhooksJob::dispatch($connection->id);
            }

            $recentWebhookCount = $connection->webhookEvents()
                ->where('created_at', '>=', now()->subDay())
                ->count();

            $recentOrderCount = $connection->syncedOrders()
                ->where('placed_at', '>=', now()->subDay())
                ->count();

            if ($recentWebhookCount === 0 && $recentOrderCount > 0) {
                Log::warning('Webhook gap detected for store connection.', [
                    'store_connection_id' => $connection->id,
                    'recent_orders' => $recentOrderCount,
                ]);
            }
        }
    }
}
