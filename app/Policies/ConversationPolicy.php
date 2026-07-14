<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domains\Chat\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->account_id !== null;
    }

    public function view(User $user, Conversation $conversation): bool
    {
        return $user->account_id === $conversation->account_id;
    }

    public function update(User $user, Conversation $conversation): bool
    {
        return $user->account_id === $conversation->account_id;
    }

    public function delete(User $user, Conversation $conversation): bool
    {
        return $user->account_id === $conversation->account_id;
    }
}
