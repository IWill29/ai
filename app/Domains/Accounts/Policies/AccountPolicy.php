<?php

declare(strict_types=1);

namespace App\Domains\Accounts\Policies;

use App\Domains\Accounts\Models\Account;
use App\Models\User;

class AccountPolicy
{
    public function view(User $user, Account $account): bool
    {
        return $user->account_id === $account->id;
    }

    public function delete(User $user, Account $account): bool
    {
        return $user->account_id === $account->id && $user->role === 'owner';
    }

    public function viewBilling(User $user, Account $account): bool
    {
        return $user->account_id === $account->id;
    }
}
