<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Domains\Stores\Enums\Platform;
use App\Domains\Stores\Models\StoreConnection;
use App\Domains\Stores\Models\StoreCredential;
use App\Models\User;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Laravel\Fortify\Features;
use Tests\TestCase;

class CsrfProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_path_is_excluded_from_csrf_verification(): void
    {
        $request = Request::create('/webhooks/shopify/connection-id', 'POST');

        $this->assertTrue($this->csrfMiddleware()->isExcluded($request));
    }

    public function test_login_path_is_not_excluded_from_csrf_verification(): void
    {
        $request = Request::create('/login', 'POST');

        $this->assertFalse($this->csrfMiddleware()->isExcluded($request));
    }

    public function test_chat_stream_path_is_not_excluded_from_csrf_verification(): void
    {
        $request = Request::create('/conversations/1/stream', 'POST');

        $this->assertFalse($this->csrfMiddleware()->isExcluded($request));
    }

    public function test_attachment_upload_path_is_not_excluded_from_csrf_verification(): void
    {
        $request = Request::create('/attachments', 'POST');

        $this->assertFalse($this->csrfMiddleware()->isExcluded($request));
    }

    public function test_post_without_token_is_rejected_when_csrf_is_enforced(): void
    {
        $this->startSession();

        $request = Request::create('/login', 'POST', [
            'email' => 'user@example.com',
            'password' => 'password',
        ]);
        $request->setLaravelSession($this->app->make('session.store'));

        $this->expectException(TokenMismatchException::class);

        $this->enforcedCsrfMiddleware()->handle($request, fn () => response('ok'));
    }

    public function test_register_path_is_not_excluded_from_csrf_verification(): void
    {
        $this->skipUnlessFortifyHas(Features::registration());

        $request = Request::create('/register', 'POST');

        $this->assertFalse($this->csrfMiddleware()->isExcluded($request));
    }

    public function test_webhook_accepts_post_without_csrf_token_when_hmac_is_valid(): void
    {
        $connection = $this->createWebhookConnection();
        $body = json_encode(['id' => 1001], JSON_THROW_ON_ERROR);
        $secret = 'test_api_secret_key_12345';
        $hmac = base64_encode(hash_hmac('sha256', $body, $secret, true));

        $this->call(
            'POST',
            route('webhooks.shopify', $connection->id),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_SHOPIFY_HMAC_SHA256' => $hmac,
                'HTTP_X_SHOPIFY_WEBHOOK_ID' => 'evt_csrf_ok',
                'HTTP_X_SHOPIFY_TOPIC' => 'products/create',
            ],
            $body,
        )->assertOk();
    }

    private function csrfMiddleware(): TestablePreventRequestForgery
    {
        return new TestablePreventRequestForgery($this->app, $this->app->make(Encrypter::class));
    }

    private function enforcedCsrfMiddleware(): TestablePreventRequestForgery
    {
        return new TestablePreventRequestForgery($this->app, $this->app->make(Encrypter::class), enforce: true);
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
}

final class TestablePreventRequestForgery extends PreventRequestForgery
{
    public function __construct(
        Application $app,
        Encrypter $encrypter,
        private readonly bool $enforce = false,
    ) {
        parent::__construct($app, $encrypter);
    }

    public function isExcluded(Request $request): bool
    {
        return $this->inExceptArray($request);
    }

    protected function runningUnitTests(): bool
    {
        return $this->enforce ? false : parent::runningUnitTests();
    }
}
