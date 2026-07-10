<?php

declare(strict_types=1);

namespace Tests\Feature\Accounts;

use App\Domains\Accounts\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_deletion_wipes_account_and_user(): void
    {
        $user = User::factory()->create();
        $accountId = $user->account_id;

        $this->actingAs($user)
            ->delete(route('profile.destroy'), [
                'password' => 'password',
            ])
            ->assertRedirect(route('home'));

        $this->assertGuest();
        $this->assertNull(User::query()->find($user->id));
        $this->assertNull(Account::query()->find($accountId));
    }
}
