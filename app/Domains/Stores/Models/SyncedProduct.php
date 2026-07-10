<?php

declare(strict_types=1);

namespace App\Domains\Stores\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SyncedProduct extends Model
{
    use HasUuids;

    protected $fillable = [
        'store_connection_id',
        'external_id',
        'title',
        'description',
        'status',
        'handle',
        'raw',
        'platform_created_at',
        'platform_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'raw' => 'array',
            'platform_created_at' => 'datetime',
            'platform_updated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<StoreConnection, $this> */
    public function storeConnection(): BelongsTo
    {
        return $this->belongsTo(StoreConnection::class);
    }

    /** @return HasMany<SyncedProductVariant, $this> */
    public function variants(): HasMany
    {
        return $this->hasMany(SyncedProductVariant::class);
    }
}
