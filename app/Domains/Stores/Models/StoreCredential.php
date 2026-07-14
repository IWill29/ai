<?php

declare(strict_types=1);

namespace App\Domains\Stores\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<string, mixed>|null $secrets
 */
class StoreCredential extends Model
{
    use HasUuids;

    /** @var list<string> */
    protected $hidden = [
        'access_token',
        'secrets',
    ];

    protected $fillable = [
        'store_connection_id',
        'access_token',
        'secrets',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'secrets' => 'encrypted:array',
        ];
    }

    /** @return BelongsTo<StoreConnection, $this> */
    public function storeConnection(): BelongsTo
    {
        return $this->belongsTo(StoreConnection::class);
    }
}
