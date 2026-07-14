<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Domains\Stores\Enums\Platform;
use App\Domains\Stores\Models\StoreConnection;
use App\Domains\Stores\Models\StoreCredential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\Features;
use Tests\Concerns\CreatesAgentFixtures;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use CreatesAgentFixtures;
    use RefreshDatabase;

    private int $ipSuffix = 10;

    protected function tearDown(): void
    {
        RateLimiter::clear('login');
        RateLimiter::clear('password-reset');
        RateLimiter::clear('agent');
        RateLimiter::clear('attachments');
        RateLimiter::clear('webhooks');

        parent::tearDown();
    }

    public function test_login_is_rate_limited(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => $this->nextIp()]);

        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertTooManyRequests();
    }

    public function test_password_reset_request_is_rate_limited(): void
    {
        $this->skipUnlessFortifyHas(Features::resetPasswords());
        $this->withServerVariables(['REMOTE_ADDR' => $this->nextIp()]);

        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->post(route('password.email'), ['email' => $user->email]);
        }

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertTooManyRequests();
    }

    public function test_chat_stream_is_rate_limited(): void
    {
        $user = User::factory()->create();
        $store = $this->createStoreForUser($user);
        $conversation = $this->createConversation($user, $store);
        $this->createOpenRouterCredential($user);

        RateLimiter::clear('agent|'.$user->account_id);

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->actingAs($user)->post(route('conversations.stream', $conversation), [
                'message' => 'Hello '.$attempt,
            ]);
        }

        $this->actingAs($user)->post(route('conversations.stream', $conversation), [
            'message' => 'One more',
        ])->assertTooManyRequests();
    }

    public function test_attachment_upload_is_rate_limited(): void
    {
        Storage::fake('attachments');

        $user = User::factory()->create();
        RateLimiter::clear('attachments|'.$user->account_id);

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $this->actingAs($user)->post(route('attachments.store'), [
                'file' => UploadedFile::fake()->createWithContent("product-{$attempt}.jpg", $this->jpegBytes()),
            ]);
        }

        $this->actingAs($user)->post(route('attachments.store'), [
            'file' => UploadedFile::fake()->createWithContent('overflow.jpg', $this->jpegBytes()),
        ])->assertTooManyRequests();
    }

    public function test_webhook_is_rate_limited(): void
    {
        Queue::fake();

        $ip = $this->nextIp();
        $this->withServerVariables(['REMOTE_ADDR' => $ip]);

        $connection = $this->createWebhookConnection();
        $body = json_encode(['id' => 1001], JSON_THROW_ON_ERROR);
        $secret = 'test_api_secret_key_12345';
        $hmac = base64_encode(hash_hmac('sha256', $body, $secret, true));
        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SHOPIFY_HMAC_SHA256' => $hmac,
            'HTTP_X_SHOPIFY_TOPIC' => 'products/create',
        ];

        RateLimiter::clear('webhooks|'.$connection->id.'|'.$ip);

        for ($attempt = 0; $attempt < 120; $attempt++) {
            $this->call(
                'POST',
                route('webhooks.shopify', $connection->id),
                [],
                [],
                [],
                array_merge($headers, [
                    'HTTP_X_SHOPIFY_WEBHOOK_ID' => 'evt_'.$attempt,
                ]),
                $body,
            )->assertOk();
        }

        $this->call(
            'POST',
            route('webhooks.shopify', $connection->id),
            [],
            [],
            [],
            array_merge($headers, [
                'HTTP_X_SHOPIFY_WEBHOOK_ID' => 'evt_overflow',
            ]),
            $body,
        )->assertTooManyRequests();
    }

    private function createWebhookConnection(): StoreConnection
    {
        $user = User::factory()->create();

        $connection = StoreConnection::query()->create([
            'account_id' => $user->account_id,
            'platform' => Platform::Shopify->value,
            'name' => 'Demo Store',
            'domain' => 'demo.myshopify.com',
            'status' => 'active',
        ]);

        StoreCredential::query()->create([
            'store_connection_id' => $connection->id,
            'access_token' => 'shpat_test_token_1234567890',
            'secrets' => ['api_secret' => 'test_api_secret_key_12345'],
        ]);

        return $connection;
    }

    private function nextIp(): string
    {
        $this->ipSuffix++;

        return '10.0.0.'.$this->ipSuffix;
    }

    private function jpegBytes(): string
    {
        return "\xFF\xD8\xFF\xE0".str_repeat("\x00", 32);
    }
}
