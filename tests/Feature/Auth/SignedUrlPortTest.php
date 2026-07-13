<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Features;
use Tests\TestCase;

class SignedUrlPortTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::emailVerification());
        config(['app.url' => 'http://127.0.0.1:8000']);
    }

    public function test_verification_signature_valid_when_host_header_omits_port(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
            absolute: false,
        );

        $path = $verificationUrl;

        $response = $this->actingAs($user)->call(
            'GET',
            $path,
            [],
            [],
            [],
            [
                'HTTP_HOST' => '127.0.0.1',
                'SERVER_PORT' => '8000',
            ],
        );

        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
}
