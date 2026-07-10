<?php

declare(strict_types=1);

namespace App\Domains\Billing\Models;

use App\Domains\Accounts\Models\Account;
use App\Domains\Stores\Models\StoreConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'account_id',
        'user_id',
        'store_connection_id',
        'action',
        'context',
        'performed_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'performed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<StoreConnection, $this> */
    public function storeConnection(): BelongsTo
    {
        return $this->belongsTo(StoreConnection::class);
    }
}
