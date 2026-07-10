<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Billing\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'free',
                'name' => 'Free',
                'price_cents' => 0,
                'currency' => 'EUR',
                'store_limit' => 1,
                'monthly_message_limit' => 100,
                'stripe_price_id' => null,
            ],
            [
                'slug' => 'pro',
                'name' => 'Pro',
                'price_cents' => 1900,
                'currency' => 'EUR',
                'store_limit' => 3,
                'monthly_message_limit' => 1000,
                'stripe_price_id' => null,
            ],
            [
                'slug' => 'business',
                'name' => 'Business',
                'price_cents' => 4900,
                'currency' => 'EUR',
                'store_limit' => null,
                'monthly_message_limit' => null,
                'stripe_price_id' => null,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::query()->updateOrCreate(
                ['slug' => $plan['slug']],
                $plan,
            );
        }
    }
}
