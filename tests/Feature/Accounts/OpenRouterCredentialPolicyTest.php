<?php

declare(strict_types=1);

namespace Tests\Feature\Accounts;

use App\Domains\Accounts\Models\OpenRouterCredential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OpenRouterCredentialPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_update_another_accounts_openrouter_credential(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $credential = OpenRouterCredential::query()->create([
            'account_id' => $owner->account_id,
            'api_key' => 'sk-or-v1-test-key-abcdefghij',
            'validated_at' => now(),
        ]);

        $this->assertFalse($intruder->can('update', $credential));
        $this->assertTrue($owner->can('update', $credential));
    }
}
