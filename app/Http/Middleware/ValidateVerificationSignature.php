<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Routing\Middleware\ValidateSignature;
use Symfony\Component\HttpFoundation\Response;

/**
 * Use relative URL signatures for email verification links so localhost and 127.0.0.1 both work.
 */
final class ValidateVerificationSignature
{
    public function __construct(
        private readonly ValidateSignature $validateSignature,
    ) {}

    /**
     * @param  string  ...$args
     */
    public function handle(Request $request, Closure $next, ...$args): Response
    {
        if ($request->routeIs('verification.verify')) {
            if ($request->hasValidSignatureWhileIgnoring([], false)) {
                return $next($request);
            }

            throw new InvalidSignatureException;
        }

        $parameters = [$request, $next, ...$args];

        return call_user_func_array($this->validateSignature->handle(...), $parameters);
    }
}
