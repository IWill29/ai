<?php

declare(strict_types=1);

namespace App\Domains\Stores\Models;

use App\Domains\Accounts\Models\Account;
use App\Domains\Billing\Models\AuditLog;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class StoreConnection extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'account_id',
        'platform',
        'name',
        'domain',
        'status',
        'last_synced_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'last_synced_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /** @return HasOne<StoreCredential, $this> */
    public function credential(): HasOne
    {
        return $this->hasOne(StoreCredential::class);
    }

    /** @return HasMany<SyncedProduct, $this> */
    public function syncedProducts(): HasMany
    {
        return $this->hasMany(SyncedProduct::class);
    }

    /** @return HasMany<SyncedOrder, $this> */
    public function syncedOrders(): HasMany
    {
        return $this->hasMany(SyncedOrder::class);
    }

    /** @return HasMany<SyncedCustomer, $this> */
    public function syncedCustomers(): HasMany
    {
        return $this->hasMany(SyncedCustomer::class);
    }

    /** @return HasMany<WebhookEvent, $this> */
    public function webhookEvents(): HasMany
    {
        return $this->hasMany(WebhookEvent::class);
    }

    /** @return HasMany<AuditLog, $this> */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
