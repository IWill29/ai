<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\Support;

use App\Domains\Stores\Adapters\Shopify\Money;

final class MoneyFormatter
{
    public static function format(int $minor, string $currency): string
    {
        $exp = Money::exponentFor($currency);
        $major = $minor / (10 ** $exp);

        return match ($currency) {
            'EUR' => '€'.number_format($major, $exp),
            'USD' => '$'.number_format($major, $exp),
            default => $currency.' '.number_format($major, $exp),
        };
    }
}
