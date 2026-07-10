<?php

declare(strict_types=1);

namespace App\Domains\Billing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasUuids;

    protected $fillable = [
        'slug',
        'name',
        'price_cents',
        'currency',
        'store_limit',
        'monthly_message_limit',
        'stripe_price_id',
    ];

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'store_limit' => 'integer',
            'monthly_message_limit' => 'integer',
        ];
    }
}
