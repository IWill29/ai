<?php

declare(strict_types=1);

namespace App\Domains\Stores\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SyncedCustomer extends Model
{
    use HasUuids;

    protected $fillable = [
        'store_connection_id',
        'external_id',
        'email',
        'name',
        'orders_count',
        'total_spent_minor',
        'currency',
        'raw',
    ];

    protected function casts(): array
    {
        return [
            'orders_count' => 'integer',
            'total_spent_minor' => 'integer',
            'raw' => 'array',
        ];
    }

    /** @return BelongsTo<StoreConnection, $this> */
    public function storeConnection(): BelongsTo
    {
        return $this->belongsTo(StoreConnection::class);
    }

    /** @return HasMany<SyncedOrder, $this> */
    public function syncedOrders(): HasMany
    {
        return $this->hasMany(SyncedOrder::class);
    }
}
