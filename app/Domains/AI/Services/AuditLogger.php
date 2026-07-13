<?php

declare(strict_types=1);

namespace App\Domains\AI\Services;

use App\Domains\Chat\Models\ActionStep;
use App\Domains\Stores\Actions\RecordStoreWriteAuditAction;

final class AuditLogger
{
    public function __construct(
        private readonly RecordStoreWriteAuditAction $recordAudit,
    ) {}

    /**
     * @param  array{ok: bool, data?: mixed, error?: string}  $result
     */
    public function logWrite(ActionStep $step, array $result): void
    {
        $conversation = $step->message->conversation;

        $this->recordAudit->execute(
            accountId: $conversation->account_id,
            userId: $conversation->user_id,
            storeConnectionId: (string) $conversation->store_connection_id,
            action: 'tool.'.$step->tool_name,
            context: [
                'arguments' => $step->arguments,
                'result' => $result['ok'] ? 'success' : 'failed',
                'summary' => $result['ok'] ? ($result['data'] ?? null) : ($result['error'] ?? null),
            ],
        );
    }
}
