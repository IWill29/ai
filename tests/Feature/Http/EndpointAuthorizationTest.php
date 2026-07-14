<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EndpointAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_page_requires_account_membership(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.billing'))
            ->assertOk();
    }

    public function test_model_allow_list_requires_account_membership(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('agent.models'))
            ->assertOk()
            ->assertJsonStructure(['tiers']);
    }
}
