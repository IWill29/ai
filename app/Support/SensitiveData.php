<?php

declare(strict_types=1);

namespace App\Support;

use Throwable;

final class SensitiveData
{
    public const REDACTED = '[redacted]';

    /** @var list<string> */
    private const SENSITIVE_KEYS = [
        'access_token',
        'api_key',
        'api_secret',
        'password',
        'secret',
        'secrets',
        'token',
        'authorization',
        'bearer',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'email',
        'phone',
        'phone_number',
        'mobile',
        'address',
        'shipping_address',
        'billing_address',
        'first_name',
        'last_name',
        'note',
        'raw',
        'line_items',
        'payload',
    ];

    /** @var list<string> */
    private const SENSITIVE_PATTERNS = [
        '/shpat_[a-zA-Z0-9]+/',
        '/sk-or-v1-[a-zA-Z0-9_-]+/',
        '/Bearer\s+[a-zA-Z0-9._-]+/i',
        '/"api_key"\s*:\s*"[^"]*"/i',
        '/"access_token"\s*:\s*"[^"]*"/i',
    ];

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public static function redactContext(array $context): array
    {
        return self::redactValue($context);
    }

    public static function sanitizeMessage(string $message): string
    {
        $message = preg_replace_callback(
            '/HTTP \d+:\s*.+/',
            static fn (array $matches): string => preg_replace('/:\s*.+$/', '.', $matches[0]) ?? $matches[0],
            $message,
        ) ?? $message;

        foreach (self::SENSITIVE_PATTERNS as $pattern) {
            $message = preg_replace($pattern, self::REDACTED, $message) ?? $message;
        }

        if (strlen($message) > 500) {
            return mb_substr($message, 0, 500).'…';
        }

        return $message;
    }

    public static function safeThrowableMessage(Throwable $throwable): string
    {
        return self::sanitizeMessage($throwable->getMessage());
    }

    private static function redactValue(mixed $value): mixed
    {
        if (! is_array($value)) {
            return is_string($value) ? self::sanitizeMessage($value) : $value;
        }

        $redacted = [];

        foreach ($value as $key => $item) {
            if (is_string($key) && self::isSensitiveKey($key)) {
                $redacted[$key] = self::REDACTED;

                continue;
            }

            $redacted[$key] = self::redactValue($item);
        }

        return $redacted;
    }

    private static function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower($key);

        foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
            if ($normalized === $sensitiveKey || str_contains($normalized, $sensitiveKey)) {
                return true;
            }
        }

        return false;
    }
}
