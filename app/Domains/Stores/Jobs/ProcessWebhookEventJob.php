<?php

declare(strict_types=1);

namespace App\Domains\Stores\Jobs;

use App\Domains\Stores\Models\WebhookEvent;
use App\Domains\Stores\Services\Sync\MirrorUpserter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class ProcessWebhookEventJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(public string $webhookEventId) {}

    public function handle(MirrorUpserter $upserter): void
    {
        $event = WebhookEvent::query()
            ->with('storeConnection')
            ->findOrFail($this->webhookEventId);

        $connection = $event->storeConnection;

        try {
            if (str_ends_with($event->topic, '/delete')) {
                $upserter->deleteFromWebhookPayload($connection, $event);
            } else {
                $upserter->upsertFromWebhookPayload($connection, $event);
            }

            $event->update([
                'status' => 'processed',
                'processed_at' => now(),
                'error' => null,
            ]);
        } catch (Throwable $exception) {
            $event->update([
                'status' => 'failed',
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
