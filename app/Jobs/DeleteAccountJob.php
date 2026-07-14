<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domains\Accounts\Actions\PurgeAccountDataAction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeleteAccountJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $accountId) {}

    public function handle(PurgeAccountDataAction $purgeAccount): void
    {
        $purgeAccount->execute($this->accountId);
    }
}
