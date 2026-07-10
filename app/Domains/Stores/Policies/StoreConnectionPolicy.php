<?php

declare(strict_types=1);

namespace App\Domains\Stores\Policies;

use App\Domains\Stores\Models\StoreConnection;
use App\Models\User;

class StoreConnectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->account_id !== null;
    }

    public function view(User $user, StoreConnection $connection): bool
    {
        return $user->account_id === $connection->account_id;
    }

    public function create(User $user): bool
    {
        return $user->account_id !== null;
    }

    public function update(User $user, StoreConnection $connection): bool
    {
        return $user->account_id === $connection->account_id;
    }

    public function delete(User $user, StoreConnection $connection): bool
    {
        return $user->account_id === $connection->account_id;
    }

    public function reconnect(User $user, StoreConnection $connection): bool
    {
        return $user->account_id === $connection->account_id;
    }
}
