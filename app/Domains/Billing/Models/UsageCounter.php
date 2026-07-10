<?php

declare(strict_types=1);

namespace App\Domains\Billing\Models;

use App\Domains\Accounts\Models\Account;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageCounter extends Model
{
    use HasUuids;

    protected $fillable = [
        'account_id',
        'period',
        'agent_messages',
    ];

    protected function casts(): array
    {
        return [
            'agent_messages' => 'integer',
        ];
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
