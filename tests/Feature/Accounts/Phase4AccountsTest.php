<?php

declare(strict_types=1);

namespace Tests\Feature\Accounts;

use App\Domains\Accounts\Models\Account;
use App\Domains\Accounts\Models\OpenRouterCredential;
use App\Domains\Chat\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

class OpenRouterKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_openrouter_settings_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.openrouter'))
            ->assertOk();
    }

    public function test_valid_openrouter_key_is_saved(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response(['data' => ['label' => 'test']]),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.openrouter.store'), [
                'api_key' => 'sk-or-v1-test-key-abcdefghij',
                'default_model' => 'openai/gpt-4o-mini',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('settings.openrouter'));

        $credential = OpenRouterCredential::query()
            ->where('account_id', $user->account_id)
            ->first();

        $this->assertNotNull($credential);
        $this->assertSame('openai/gpt-4o-mini', $credential->default_model);
        $this->assertNotNull($credential->validated_at);
    }

    public function test_invalid_openrouter_key_is_rejected(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response(status: 401),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('settings.openrouter'))
            ->post(route('settings.openrouter.store'), [
                'api_key' => 'sk-or-v1-invalid-key-abcdefghij',
            ])
            ->assertSessionHasErrors('api_key');

        $this->assertNull(
            OpenRouterCredential::query()->where('account_id', $user->account_id)->first(),
        );
    }
}

class ConversationPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_view_another_accounts_conversation(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $conversation = Conversation::query()->create([
            'account_id' => $owner->account_id,
            'user_id' => $owner->id,
            'title' => 'Ops',
            'model' => 'openai/gpt-4o-mini',
        ]);

        $this->assertFalse($intruder->can('view', $conversation));
        $this->assertTrue($owner->can('view', $conversation));
    }
}

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
