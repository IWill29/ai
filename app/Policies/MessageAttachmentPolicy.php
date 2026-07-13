<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domains\Chat\Models\MessageAttachment;
use App\Models\User;

class MessageAttachmentPolicy
{
    public function view(User $user, MessageAttachment $attachment): bool
    {
        return $user->account_id === $attachment->account_id;
    }
}
