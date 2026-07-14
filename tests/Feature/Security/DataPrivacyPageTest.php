<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DataPrivacyPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_data_privacy_page_is_displayed_for_verified_users(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.data-privacy'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('settings/data-privacy')
                ->has('note.title')
                ->has('note.sections', 7));
    }
}
