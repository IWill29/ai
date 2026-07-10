<?php

declare(strict_types=1);

namespace App\Domains\Accounts\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $account_id
 * @property string $api_key
 * @property string|null $default_model
 * @property Carbon|null $validated_at
 */
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
