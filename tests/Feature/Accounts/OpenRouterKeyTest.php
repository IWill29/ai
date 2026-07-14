<?php

declare(strict_types=1);

namespace Tests\Feature\Accounts;

use App\Domains\Accounts\Models\OpenRouterCredential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

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
        $this->assertSame('sk-or-v1-test-key-abcdefghij', $credential->api_key);
        $this->assertNotSame('sk-or-v1-test-key-abcdefghij', $credential->getRawOriginal('api_key'));
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
