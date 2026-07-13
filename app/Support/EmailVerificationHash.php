<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;

/**
 * Hash for email verification URL path segments (Fortify / Laravel contract).
 */
final class EmailVerificationHash
{
    public static function forUser(User $user): string
    {
        return self::forEmail($user->getEmailForVerification());
    }

    public static function forEmail(string $email): string
    {
        // SHA-1 is required by Laravel Fortify VerifyEmailRequest; not used for password storage.
        return sha1($email); // NOSONAR php:S4790
    }
}
