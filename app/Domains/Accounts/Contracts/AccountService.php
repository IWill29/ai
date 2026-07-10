<?php

declare(strict_types=1);

namespace App\Domains\Accounts\Contracts;

/**
 * Account lifecycle — registration, GDPR delete, BYOK key storage.
 */
interface AccountService
{
    /** Create an account for a newly registered user; returns accountId. */
    public function createForRegistration(string $userId, string $name): string;

    /**
     * GDPR delete: cancel billing → purge stores/credentials/synced/chat/memories.
     */
    public function deleteAccount(string $accountId): void;

    public function saveOpenRouterKey(string $accountId, string $apiKey, ?string $defaultModel): void;
}
