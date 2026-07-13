<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirect only when the request hostname differs from APP_URL.
 * Avoids redirect loops behind nginx where Host may omit the public port.
 */
final class RedirectToConfiguredAppHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $appUrl = config('app.url');

        if (! is_string($appUrl) || $appUrl === '') {
            return $next($request);
        }

        $expectedHost = parse_url($appUrl, PHP_URL_HOST);

        if (! is_string($expectedHost) || $expectedHost === '' || strcasecmp($request->getHost(), $expectedHost) === 0) {
            return $next($request);
        }

        $canonicalRoot = rtrim($appUrl, '/');

        return redirect()->to($canonicalRoot.$request->getRequestUri(), Response::HTTP_MOVED_PERMANENTLY);
    }
}
