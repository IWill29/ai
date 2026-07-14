<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

/**
 * Always require a matching CSRF token — do not bypass via Sec-Fetch-Site.
 */
class VerifyCsrfToken extends PreventRequestForgery
{
    protected function hasValidOrigin($request): bool
    {
        return false;
    }
}
