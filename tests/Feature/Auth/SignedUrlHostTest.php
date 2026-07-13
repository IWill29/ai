<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\EmailVerificationHash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Features;
use Tests\TestCase;

class SignedUrlHostTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::emailVerification());
    }

    public function test_invalid_verification_signature_redirects_authenticated_user_with_error(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/email/verify/1/invalid?expires=9999999999&signature=bad');

        $response->assertRedirect(route('verification.notice'));
        $response->assertSessionHas('error');
    }

    public function test_signed_verification_url_works_after_localhost_redirect(): void
    {
        config(['app.url' => 'http://127.0.0.1:8000']);

        $user = User::factory()->unverified()->create();

        $relative = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => EmailVerificationHash::forUser($user)],
            absolute: false,
        );

        $this->assertStringStartsWith('/email/verify/', $relative);

        $response = $this->actingAs($user)->call(
            'GET',
            $relative,
            [],
            [],
            [],
            ['HTTP_HOST' => 'localhost:8000'],
        );

        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
}
