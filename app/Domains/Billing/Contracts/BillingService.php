<?php

declare(strict_types=1);

namespace App\Domains\Billing\Contracts;

use App\Domains\Billing\DTOs\PlanDTO;

/**
 * Account-scoped billing limits and usage — never sees chat content.
 * Phase 11 swaps StubBillingService → DefaultBillingService (Cashier).
 */
interface BillingService
{
    public function getPlan(string $accountId): PlanDTO;

    /** True if the account is within its monthly agent-message limit. */
    public function canSendMessage(string $accountId): bool;

    /** True if the account can connect another store. */
    public function canConnectStore(string $accountId): bool;

    public function incrementMessageCount(string $accountId): void;

    public function cancelSubscription(string $accountId): void;
}
