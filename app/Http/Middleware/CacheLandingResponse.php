<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

final class CacheLandingResponse
{
    private const CACHE_TTL_SECONDS = 3600;

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldUseCache($request)) {
            return $next($request);
        }

        $appearance = $request->cookie('appearance', 'system');
        $cacheKey = 'landing.response.'.$appearance;

        /** @var array{content: string, headers: array<string, string>}|null $cached */
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return response($cached['content'], 200, [
                ...$cached['headers'],
                'Cache-Control' => 'public, max-age=300',
                'X-Landing-Cache' => 'HIT',
            ]);
        }

        /** @var Response $response */
        $response = $next($request);

        if ($response->isSuccessful() && $response->getContent() !== false) {
            Cache::put($cacheKey, [
                'content' => $response->getContent(),
                'headers' => [
                    'Content-Type' => $response->headers->get('Content-Type', 'text/html; charset=UTF-8'),
                ],
            ], self::CACHE_TTL_SECONDS);

            $response->headers->set('Cache-Control', 'public, max-age=300');
            $response->headers->set('X-Landing-Cache', 'MISS');
        }

        return $response;
    }

    private function shouldUseCache(Request $request): bool
    {
        if (app()->environment('local') || config('app.debug')) {
            return false;
        }

        if (! $request->isMethod('GET') || $request->user() !== null || ! $request->routeIs('home')) {
            return false;
        }

        if ($request->expectsJson()) {
            return false;
        }

        if ($request->hasSession() && $request->session()->has('errors')) {
            return false;
        }

        return true;
    }
}
