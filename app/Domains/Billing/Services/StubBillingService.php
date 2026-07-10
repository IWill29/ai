<?php

declare(strict_types=1);

namespace App\Domains\Billing\Services;

use App\Domains\Billing\Contracts\BillingService;
use App\Domains\Billing\DTOs\PlanDTO;
use App\Domains\Billing\Models\Plan;
use App\Domains\Billing\Models\UsageCounter;
use App\Domains\Stores\Models\StoreConnection;
use Illuminate\Support\Carbon;

/**
 * Reads limits from plans + usage_counters — no Stripe until Phase 11.
 */
final class StubBillingService implements BillingService
{
    public function getPlan(string $accountId): PlanDTO
    {
        $plan = Plan::query()->where('slug', 'free')->firstOrFail();

        return $this->toDto($plan);
    }

    public function canSendMessage(string $accountId): bool
    {
        $plan = $this->getPlan($accountId);

        if ($plan->monthlyMessageLimit === null) {
            return true;
        }

        $counter = $this->currentCounter($accountId);

        return $counter->agent_messages < $plan->monthlyMessageLimit;
    }

    public function canConnectStore(string $accountId): bool
    {
        $plan = $this->getPlan($accountId);

        if ($plan->storeLimit === null) {
            return true;
        }

        $activeStores = StoreConnection::query()
            ->where('account_id', $accountId)
            ->whereNull('deleted_at')
            ->count();

        return $activeStores < $plan->storeLimit;
    }

    public function incrementMessageCount(string $accountId): void
    {
        $counter = $this->currentCounter($accountId);
        $counter->increment('agent_messages');
    }

    public function cancelSubscription(string $accountId): void
    {
        // no-op until Phase 11 (Cashier)
    }

    private function currentCounter(string $accountId): UsageCounter
    {
        return UsageCounter::query()->firstOrCreate(
            [
                'account_id' => $accountId,
                'period' => Carbon::now()->format('Y-m'),
            ],
            ['agent_messages' => 0],
        );
    }

    private function toDto(Plan $plan): PlanDTO
    {
        return new PlanDTO(
            slug: $plan->slug,
            name: $plan->name,
            priceCents: $plan->price_cents,
            currency: $plan->currency,
            storeLimit: $plan->store_limit,
            monthlyMessageLimit: $plan->monthly_message_limit,
        );
    }
}
