<?php

declare(strict_types=1);

namespace App\Domains\Accounts\Actions;

use App\Domains\Accounts\Models\Account;
use App\Domains\Billing\Contracts\BillingService;
use App\Domains\Chat\Models\MessageAttachment;
use App\Support\AttachmentStorage;
use Illuminate\Support\Facades\DB;

final class PurgeAccountDataAction
{
    public function __construct(
        private readonly BillingService $billing,
    ) {}

    public function execute(string $accountId): void
    {
        DB::transaction(function () use ($accountId): void {
            $account = Account::withTrashed()->findOrFail($accountId);

            foreach ($account->storeConnections()->withTrashed()->get() as $storeConnection) {
                $storeConnection->forceDelete();
            }

            $account->forceDelete();
        });

        $this->deleteAttachmentFiles($accountId);
        $this->billing->cancelSubscription($accountId);
    }

    private function deleteAttachmentFiles(string $accountId): void
    {
        $disk = AttachmentStorage::disk();

        MessageAttachment::query()
            ->where('account_id', $accountId)
            ->pluck('storage_path')
            ->each(function (string $path) use ($disk): void {
                if ($path !== '' && $disk->exists($path)) {
                    $disk->delete($path);
                }
            });

        $prefix = $accountId.'/';

        if ($disk->exists($prefix)) {
            $disk->deleteDirectory($prefix);
        }
    }
}
