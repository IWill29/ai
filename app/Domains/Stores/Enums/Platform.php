<?php

declare(strict_types=1);

namespace App\Domains\Stores\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum Platform: string
{
    case Shopify = 'shopify';
    case WooCommerce = 'woocommerce';

    public function label(): string
    {
        return match ($this) {
            self::Shopify => 'Shopify',
            self::WooCommerce => 'WooCommerce',
        };
    }
}
