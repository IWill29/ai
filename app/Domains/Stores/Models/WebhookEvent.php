<?php

declare(strict_types=1);

namespace App\Domains\Stores\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookEvent extends Model
{
    use HasUuids;

    protected $fillable = [
        'store_connection_id',
        'platform',
        'topic',
        'external_event_id',
        'status',
        'payload',
        'error',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<StoreConnection, $this> */
    public function storeConnection(): BelongsTo
    {
        return $this->belongsTo(StoreConnection::class);
    }
}
