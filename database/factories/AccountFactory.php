<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Accounts\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company().' workspace',
            'locale' => 'en',
        ];
    }
}
