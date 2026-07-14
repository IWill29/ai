<?php

declare(strict_types=1);

namespace App\Domains\Stores\Actions;

use App\Domains\Billing\Actions\RecordAuditAction;
use App\Domains\Stores\Models\StoreConnection;
use Illuminate\Support\Facades\DB;

final class DisconnectStoreAction
{
    public function __construct(
        private readonly RecordAuditAction $recordAudit,
    ) {}

    public function execute(StoreConnection $connection, int $userId): void
    {
        DB::transaction(function () use ($connection, $userId): void {
            $this->recordAudit->execute(
                accountId: $connection->account_id,
                userId: $userId,
                storeConnectionId: $connection->id,
                action: 'store.disconnect',
                context: [
                    'platform' => $connection->platform,
                    'domain' => $connection->domain,
                    'name' => $connection->name,
                ],
            );

            $connection->forceDelete();
        });
    }
}
