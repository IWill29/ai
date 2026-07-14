<?php

declare(strict_types=1);

namespace Tests\Feature\Accounts;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AccountPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_owner_can_delete_account(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $member = User::factory()->create([
            'account_id' => $owner->account_id,
            'role' => 'member',
        ]);

        $account = $owner->account;

        $this->assertNotNull($account);
        $this->assertTrue($owner->can('delete', $account));
        $this->assertFalse($member->can('delete', $account));
    }
}
