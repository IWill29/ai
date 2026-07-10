<?php

declare(strict_types=1);

namespace App\Domains\Chat\Models;

use App\Domains\Accounts\Models\Account;
use App\Domains\Stores\Models\StoreConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'account_id',
        'user_id',
        'store_connection_id',
        'title',
        'model',
    ];

    protected function casts(): array
    {
        return [
            'deleted_at' => 'datetime',
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

    /** @return HasMany<Message, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
