<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RedirectToConfiguredAppHost;
use App\Http\Middleware\ValidateVerificationSignature;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
        ]);

        $middleware->replace(PreventRequestForgery::class, VerifyCsrfToken::class);

        $middleware->web(prepend: [
            RedirectToConfiguredAppHost::class,
        ]);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'signed' => ValidateVerificationSignature::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (InvalidSignatureException $_, Request $request): ?Response {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'This link is invalid or has expired.',
                ], Response::HTTP_FORBIDDEN);
            }

            if (! $request->is('email/verify/*')) {
                return null;
            }

            $message = 'This verification link is invalid or has expired. Request a new one below.';

            if ($request->user() === null) {
                $message .= ' Log in first, then use Resend verification email.';
            }

            $route = $request->user() !== null ? 'verification.notice' : 'login';

            return redirect()->route($route)->with('error', $message);
        });
    })->create();
