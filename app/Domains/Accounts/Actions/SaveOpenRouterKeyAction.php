<?php

declare(strict_types=1);

namespace App\Domains\Accounts\Actions;

use App\Domains\Accounts\Contracts\AccountService;
use App\Domains\Accounts\Exceptions\InvalidApiKeyException;
use App\Domains\Billing\Actions\RecordAuditAction;
use Illuminate\Support\Facades\Http;

final class SaveOpenRouterKeyAction
{
    public function __construct(
        private readonly AccountService $accounts,
        private readonly RecordAuditAction $recordAudit,
    ) {}

    public function execute(string $accountId, int $userId, string $apiKey, ?string $defaultModel): void
    {
        $response = Http::withToken($apiKey)
            ->timeout(10)
            ->get('https://openrouter.ai/api/v1/key');

        if ($response->failed()) {
            throw new InvalidApiKeyException('OpenRouter rejected this API key.');
        }

        $this->accounts->saveOpenRouterKey($accountId, $apiKey, $defaultModel);

        $this->recordAudit->execute(
            accountId: $accountId,
            userId: $userId,
            storeConnectionId: null,
            action: 'openrouter.key.save',
            context: [
                'default_model' => $defaultModel,
            ],
        );
    }
}
