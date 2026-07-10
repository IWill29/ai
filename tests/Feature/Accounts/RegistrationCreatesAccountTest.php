<?php

declare(strict_types=1);

namespace Tests\Feature\Accounts;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationCreatesAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_account_for_user(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Merchant',
            'email' => 'merchant@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $user = User::query()->firstWhere('email', 'merchant@example.com');

        $this->assertNotNull($user);
        $this->assertNotNull($user->account_id);
        $this->assertSame('owner', $user->role);
        $this->assertDatabaseHas('accounts', ['id' => $user->account_id]);
    }
}
