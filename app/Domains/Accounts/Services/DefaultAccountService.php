<?php

declare(strict_types=1);

namespace App\Domains\Accounts\Services;

use App\Domains\Accounts\Contracts\AccountService;
use BadMethodCallException;

/**
 * Placeholder until Phase 4 (registration, GDPR delete, BYOK).
 */
final class DefaultAccountService implements AccountService
{
    public function createForRegistration(string $userId, string $name): string
    {
        throw new BadMethodCallException('AccountService not implemented until Phase 4.');
    }

    public function deleteAccount(string $accountId): void
    {
        throw new BadMethodCallException('AccountService not implemented until Phase 4.');
    }

    public function saveOpenRouterKey(string $accountId, string $apiKey, ?string $defaultModel): void
    {
        throw new BadMethodCallException('AccountService not implemented until Phase 4.');
    }
}
