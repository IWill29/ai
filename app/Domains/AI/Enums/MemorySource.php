<?php

declare(strict_types=1);

namespace App\Domains\AI\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum MemorySource: string
{
    case ConfirmedAction = 'confirmed_action';
    case MerchantPreference = 'merchant_preference';
}
