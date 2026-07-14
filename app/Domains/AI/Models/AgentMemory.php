<?php

declare(strict_types=1);

namespace App\Domains\AI\Models;

use App\Domains\Accounts\Models\Account;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pgvector\Laravel\HasNeighbors;
use Pgvector\Laravel\Vector;

/**
 * @property array<string, mixed>|null $meta
 */
class AgentMemory extends Model
{
    use HasNeighbors, HasUuids;

    protected $fillable = [
        'account_id',
        'content',
        'embedding',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'embedding' => Vector::class,
            'meta' => 'array',
        ];
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
