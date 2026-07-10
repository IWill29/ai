<?php

declare(strict_types=1);

namespace Tests\Concerns;

use Database\Seeders\PlanSeeder;

trait SeedsPlans
{
    protected function seedPlans(): void
    {
        $this->seed(PlanSeeder::class);
    }
}
