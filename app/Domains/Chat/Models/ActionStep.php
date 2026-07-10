<?php

declare(strict_types=1);

namespace App\Domains\Chat\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActionStep extends Model
{
    use HasUuids;

    protected $fillable = [
        'message_id',
        'step_order',
        'tool_name',
        'arguments',
        'target_platform',
        'status',
        'is_write',
        'confirmed',
        'result_summary',
        'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'arguments' => 'array',
            'result_summary' => 'array',
            'is_write' => 'boolean',
            'confirmed' => 'boolean',
            'step_order' => 'integer',
            'duration_ms' => 'integer',
        ];
    }

    /** @return BelongsTo<Message, $this> */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}
