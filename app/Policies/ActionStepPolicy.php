<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domains\Chat\Models\ActionStep;
use App\Models\User;

class ActionStepPolicy
{
    public function view(User $user, ActionStep $step): bool
    {
        return $user->account_id === $step->message->conversation->account_id;
    }

    public function update(User $user, ActionStep $step): bool
    {
        return $user->account_id === $step->message->conversation->account_id;
    }
}
