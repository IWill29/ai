<?php

declare(strict_types=1);

namespace App\Domains\Stores\Actions;

use App\Domains\Billing\Models\AuditLog;
use Illuminate\Support\Carbon;

final class RecordStoreWriteAuditAction
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function execute(
        string $accountId,
        ?int $userId,
        string $storeConnectionId,
        string $action,
        array $context = [],
    ): AuditLog {
        return AuditLog::query()->create([
            'account_id' => $accountId,
            'user_id' => $userId,
            'store_connection_id' => $storeConnectionId,
            'action' => $action,
            'context' => $context,
            'performed_at' => Carbon::now(),
        ]);
    }
}
