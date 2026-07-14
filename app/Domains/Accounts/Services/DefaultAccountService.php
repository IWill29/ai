<?php

declare(strict_types=1);

namespace App\Domains\Accounts\Services;

use App\Domains\Accounts\Contracts\AccountService;
use App\Domains\Accounts\Models\Account;
use App\Domains\Accounts\Models\OpenRouterCredential;
use App\Jobs\DeleteAccountJob;
use Illuminate\Support\Facades\DB;

final class DefaultAccountService implements AccountService
{
    public function createForRegistration(string $userId, string $name): string
    {
        return DB::transaction(function () use ($name): string {
            $account = Account::query()->create([
                'name' => $name,
                'locale' => 'en',
            ]);

            return $account->id;
        });
    }

    public function saveOpenRouterKey(string $accountId, string $apiKey, ?string $defaultModel): void
    {
        OpenRouterCredential::query()->updateOrCreate(
            ['account_id' => $accountId],
            [
                'api_key' => $apiKey,
                'default_model' => $defaultModel,
                'validated_at' => now(),
            ],
        );
    }

    public function deleteAccount(string $accountId): void
    {
        DeleteAccountJob::dispatchSync($accountId);
    }
}
