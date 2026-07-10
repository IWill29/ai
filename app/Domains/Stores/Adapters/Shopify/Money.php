<?php

declare(strict_types=1);

namespace App\Domains\Stores\Adapters\Shopify;

use App\Domains\Stores\Exceptions\StoreApiException;

final class Money
{
    /**
     * ISO 4217 minor-unit exponents. Default is 2; only exceptions are listed.
     *
     * @var array<string, int>
     */
    private const EXPONENTS = [
        'JPY' => 0, 'KRW' => 0, 'VND' => 0, 'CLP' => 0, 'ISK' => 0,
        'UGX' => 0, 'XAF' => 0, 'XOF' => 0, 'XPF' => 0, 'RWF' => 0,
        'BIF' => 0, 'DJF' => 0, 'GNF' => 0, 'KMF' => 0, 'PYG' => 0,
        'BHD' => 3, 'KWD' => 3, 'OMR' => 3, 'TND' => 3, 'JOD' => 3,
        'IQD' => 3, 'LYD' => 3,
    ];

    public static function toMinor(?string $amount, ?string $currency): int
    {
        if ($amount === null) {
            return 0;
        }

        $exp = self::exponentFor($currency);

        return (int) round(((float) $amount) * (10 ** $exp));
    }

    public static function exponentFor(?string $currency): int
    {
        if ($currency === null) {
            throw new StoreApiException('Missing currency code for money value.');
        }

        return self::EXPONENTS[strtoupper($currency)] ?? 2;
    }
}
