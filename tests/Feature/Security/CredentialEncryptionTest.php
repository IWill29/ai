<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Domains\Accounts\Models\OpenRouterCredential;
use App\Domains\Stores\Enums\Platform;
use App\Domains\Stores\Models\StoreConnection;
use App\Domains\Stores\Models\StoreCredential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CredentialEncryptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_access_token_is_encrypted_at_rest(): void
    {
        $credential = $this->createStoreCredential(
            accessToken: 'shpat_test_token_1234567890',
            apiSecret: 'test_api_secret_key_12345',
        );

        $this->assertSame('shpat_test_token_1234567890', $credential->access_token);
        $this->assertNotSame('shpat_test_token_1234567890', $credential->getRawOriginal('access_token'));
    }

    public function test_store_api_secret_is_encrypted_at_rest(): void
    {
        $credential = $this->createStoreCredential(
            accessToken: 'shpat_test_token_1234567890',
            apiSecret: 'test_api_secret_key_12345',
        );

        $this->assertSame('test_api_secret_key_12345', $credential->secrets['api_secret']);
        $this->assertStringNotContainsString('test_api_secret_key_12345', (string) $credential->getRawOriginal('secrets'));
    }

    public function test_openrouter_api_key_is_encrypted_at_rest(): void
    {
        $user = User::factory()->create();

        $credential = OpenRouterCredential::query()->create([
            'account_id' => $user->account_id,
            'api_key' => 'sk-or-v1-test-key-abcdefghij',
            'validated_at' => now(),
        ]);

        $this->assertSame('sk-or-v1-test-key-abcdefghij', $credential->api_key);
        $this->assertNotSame('sk-or-v1-test-key-abcdefghij', $credential->getRawOriginal('api_key'));
    }

    public function test_two_factor_secrets_are_encrypted_at_rest(): void
    {
        $user = User::factory()->withTwoFactor()->create();

        $this->assertSame('secret', $user->two_factor_secret);
        $this->assertNotSame('secret', $user->getRawOriginal('two_factor_secret'));
        $this->assertSame(['recovery-code-1'], $user->two_factor_recovery_codes);
        $this->assertStringNotContainsString('recovery-code-1', (string) $user->getRawOriginal('two_factor_recovery_codes'));
    }

    private function createStoreCredential(string $accessToken, string $apiSecret): StoreCredential
    {
        $user = User::factory()->create();

        $connection = StoreConnection::query()->create([
            'account_id' => $user->account_id,
            'platform' => Platform::Shopify->value,
            'name' => 'Demo Store',
            'domain' => 'demo.myshopify.com',
            'status' => 'active',
        ]);

        return StoreCredential::query()->create([
            'store_connection_id' => $connection->id,
            'access_token' => $accessToken,
            'secrets' => ['api_secret' => $apiSecret],
        ]);
    }
}
