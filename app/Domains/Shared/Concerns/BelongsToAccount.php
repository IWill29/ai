<?php

declare(strict_types=1);

namespace App\Domains\Shared\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToAccount
{
    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForAccount(Builder $query, string $accountId): Builder
    {
        return $query->where($this->getTable().'.account_id', $accountId);
    }
}
