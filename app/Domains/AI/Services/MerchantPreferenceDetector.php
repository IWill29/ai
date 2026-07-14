<?php

declare(strict_types=1);

namespace App\Domains\AI\Services;

final class MerchantPreferenceDetector
{
    public function extract(string $message): ?string
    {
        $normalized = trim($message);

        if ($normalized === '') {
            return null;
        }

        $patterns = [
            '/^(?:please\s+)?remember(?:\s+that)?\s+(.+)$/iu',
            '/^(?:from\s+now\s+on|always)\s*,?\s*(.+)$/iu',
            '/^my\s+preference\s+is\s+(.+)$/iu',
            '/^i\s+prefer\s+(?:to\s+)?(.+)$/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $normalized, $matches) === 1) {
                $preference = trim($matches[1]);

                return $preference !== '' ? $preference : null;
            }
        }

        return null;
    }
}
