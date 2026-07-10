<?php

declare(strict_types=1);

namespace App\Domains\Stores\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncedProductVariant extends Model
{
    use HasUuids;

    protected $fillable = [
        'synced_product_id',
        'external_id',
        'sku',
        'title',
        'price_minor',
        'currency',
        'inventory_quantity',
        'raw',
    ];

    protected function casts(): array
    {
        return [
            'price_minor' => 'integer',
            'raw' => 'array',
        ];
    }

    /** @return BelongsTo<SyncedProduct, $this> */
    public function syncedProduct(): BelongsTo
    {
        return $this->belongsTo(SyncedProduct::class);
    }
}
