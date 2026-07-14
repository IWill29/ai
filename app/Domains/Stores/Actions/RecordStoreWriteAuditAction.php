<?php

declare(strict_types=1);

namespace App\Domains\Stores\Actions;

use App\Domains\Billing\Actions\RecordAuditAction;

/**
 * @deprecated Use RecordAuditAction directly.
 */
final class RecordStoreWriteAuditAction
{
    public function __construct(
        private readonly RecordAuditAction $recordAudit,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function execute(
        string $accountId,
        ?int $userId,
        string $storeConnectionId,
        string $action,
        array $context = [],
    ): \App\Domains\Billing\Models\AuditLog {
        return $this->recordAudit->execute(
            accountId: $accountId,
            userId: $userId,
            storeConnectionId: $storeConnectionId,
            action: $action,
            context: $context,
        );
    }
}
