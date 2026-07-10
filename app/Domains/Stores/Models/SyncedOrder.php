<?php

declare(strict_types=1);

namespace App\Domains\Stores\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncedOrder extends Model
{
    use HasUuids;

    protected $fillable = [
        'store_connection_id',
        'synced_customer_id',
        'external_id',
        'order_number',
        'financial_status',
        'fulfillment_status',
        'total_price_minor',
        'currency',
        'line_items',
        'raw',
        'placed_at',
    ];

    protected function casts(): array
    {
        return [
            'total_price_minor' => 'integer',
            'line_items' => 'array',
            'raw' => 'array',
            'placed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<StoreConnection, $this> */
    public function storeConnection(): BelongsTo
    {
        return $this->belongsTo(StoreConnection::class);
    }

    /** @return BelongsTo<SyncedCustomer, $this> */
    public function syncedCustomer(): BelongsTo
    {
        return $this->belongsTo(SyncedCustomer::class);
    }
}
