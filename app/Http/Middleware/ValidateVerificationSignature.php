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
final class ValidateVerificationSignature extends ValidateSignature
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle($request, Closure $next, ...$args)
    {
        if ($request->routeIs('verification.verify')) {
            [$relative, $ignore] = $this->parseArguments(['relative', ...$args]);

            if ($request->hasValidSignatureWhileIgnoring($ignore, ! $relative)) {
                return $next($request);
            }

            throw new InvalidSignatureException;
        }

        return parent::handle($request, $next, ...$args);
    }
}
