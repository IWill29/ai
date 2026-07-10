<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\Stores\Models\WebhookEvent;
use Illuminate\Console\Command;

final class PruneWebhookEventsCommand extends Command
{
    protected $signature = 'webhooks:prune';

    protected $description = 'Prune processed webhook events older than 90 days';

    public function handle(): int
    {
        $deleted = WebhookEvent::query()
            ->where('status', 'processed')
            ->where('processed_at', '<', now()->subDays(90))
            ->delete();

        $this->info("Pruned {$deleted} processed webhook events.");

        return self::SUCCESS;
    }
}
