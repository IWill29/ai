<?php

declare(strict_types=1);

namespace App\Domains\Accounts\Models;

use App\Domains\AI\Models\AgentMemory;
use App\Domains\Billing\Models\AuditLog;
use App\Domains\Billing\Models\UsageCounter;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Chat\Models\MessageAttachment;
use App\Domains\Stores\Models\StoreConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'locale',
    ];

    protected function casts(): array
    {
        return [
            'deleted_at' => 'datetime',
        ];
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasOne<OpenRouterCredential, $this> */
    public function openRouterCredential(): HasOne
    {
        return $this->hasOne(OpenRouterCredential::class);
    }

    /** @return HasMany<StoreConnection, $this> */
    public function storeConnections(): HasMany
    {
        return $this->hasMany(StoreConnection::class);
    }

    /** @return HasMany<Conversation, $this> */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /** @return HasMany<AgentMemory, $this> */
    public function agentMemories(): HasMany
    {
        return $this->hasMany(AgentMemory::class);
    }

    /** @return HasMany<UsageCounter, $this> */
    public function usageCounters(): HasMany
    {
        return $this->hasMany(UsageCounter::class);
    }

    /** @return HasMany<AuditLog, $this> */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /** @return HasMany<MessageAttachment, $this> */
    public function messageAttachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }
}
