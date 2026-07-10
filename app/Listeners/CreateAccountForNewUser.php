<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Domains\Accounts\Contracts\AccountService;
use App\Models\User;
use Illuminate\Auth\Events\Registered;

class CreateAccountForNewUser
{
    public function __construct(private readonly AccountService $accounts) {}

    public function handle(Registered $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        if ($user->account_id !== null) {
            return;
        }

        $accountId = $this->accounts->createForRegistration(
            userId: (string) $user->id,
            name: $user->name."'s workspace",
        );

        $user->forceFill([
            'account_id' => $accountId,
            'role' => 'owner',
        ])->save();
    }
}
