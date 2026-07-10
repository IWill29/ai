<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domains\Accounts\Models\Account;
use App\Domains\Billing\Contracts\BillingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class DeleteAccountJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $accountId) {}

    public function handle(BillingService $billing): void
    {
        DB::transaction(function () use ($billing): void {
            $account = Account::withTrashed()->findOrFail($this->accountId);

            $billing->cancelSubscription($this->accountId);

            foreach ($account->storeConnections()->withTrashed()->get() as $storeConnection) {
                $storeConnection->forceDelete();
            }

            $account->forceDelete();
        });
    }
}
