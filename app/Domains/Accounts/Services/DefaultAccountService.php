<?php

declare(strict_types=1);

namespace App\Domains\Accounts\Services;

use App\Domains\Accounts\Contracts\AccountService;
use App\Domains\Shared\Concerns\DefersImplementation;

/**
 * Placeholder until Phase 4 (registration, GDPR delete, BYOK).
 */
final class DefaultAccountService implements AccountService
{
    use DefersImplementation;

    public function createForRegistration(string $userId, string $name): string
    {
        $this->notImplemented('AccountService');
    }

    public function deleteAccount(string $accountId): void
    {
        $this->notImplemented('AccountService');
    }

    public function saveOpenRouterKey(string $accountId, string $apiKey, ?string $defaultModel): void
    {
        $this->notImplemented('AccountService');
    }
}
