<?php

declare(strict_types=1);

namespace App\Domains\Accounts\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpenRouterCredential extends Model
{
    use HasUuids;

    protected $fillable = [
        'account_id',
        'api_key',
        'default_model',
        'validated_at',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'validated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
