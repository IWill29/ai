<?php

declare(strict_types=1);

namespace App\Domains\Accounts\Policies;

use App\Domains\Accounts\Models\OpenRouterCredential;
use App\Models\User;

class OpenRouterCredentialPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->account_id !== null;
    }

    public function view(User $user, OpenRouterCredential $credential): bool
    {
        return $user->account_id === $credential->account_id;
    }

    public function create(User $user): bool
    {
        return $user->account_id !== null;
    }

    public function update(User $user, OpenRouterCredential $credential): bool
    {
        return $user->account_id === $credential->account_id;
    }
}
