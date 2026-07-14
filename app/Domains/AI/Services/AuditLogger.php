<?php

declare(strict_types=1);

namespace App\Domains\AI\Services;

use App\Domains\Chat\Models\ActionStep;
use App\Domains\Billing\Actions\RecordAuditAction;
use App\Support\SensitiveData;

final class AuditLogger
{
    public function __construct(
        private readonly RecordAuditAction $recordAudit,
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
                'arguments' => SensitiveData::redactContext($step->arguments ?? []),
                'result' => $result['ok'] ? 'success' : 'failed',
                'summary' => $result['ok']
                    ? SensitiveData::redactContext(is_array($result['data'] ?? null) ? $result['data'] : [])
                    : SensitiveData::sanitizeMessage((string) ($result['error'] ?? 'Tool execution failed.')),
            ],
        );
    }
}
