<?php

declare(strict_types=1);

namespace App\Domains\Accounts\Actions;

use App\Domains\Accounts\Contracts\AccountService;
use App\Domains\Accounts\Exceptions\InvalidApiKeyException;
use Illuminate\Support\Facades\Http;

final class SaveOpenRouterKeyAction
{
    public function __construct(private readonly AccountService $accounts) {}

    public function execute(string $accountId, string $apiKey, ?string $defaultModel): void
    {
        $response = Http::withToken($apiKey)
            ->timeout(10)
            ->get('https://openrouter.ai/api/v1/key');

        if ($response->failed()) {
            throw new InvalidApiKeyException('OpenRouter rejected this API key.');
        }

        $this->accounts->saveOpenRouterKey($accountId, $apiKey, $defaultModel);
    }
}
