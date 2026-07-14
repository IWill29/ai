<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

trait InteractsWithOwnedResources
{
    protected function ownedStoreConnectionRule(): Exists
    {
        return Rule::exists('store_connections', 'id')->where(
            fn ($query) => $query->where('account_id', $this->user()?->account_id),
        );
    }

    protected function ownedPendingAttachmentRule(): Exists
    {
        return Rule::exists('message_attachments', 'id')->where(
            fn ($query) => $query
                ->where('account_id', $this->user()?->account_id)
                ->where('status', 'pending')
                ->where('expires_at', '>', now()),
        );
    }
}
