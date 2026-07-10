<?php

declare(strict_types=1);

namespace App\Domains\Chat\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum MessageRole: string
{
    case User = 'user';
    case Assistant = 'assistant';
    case System = 'system';
    case Tool = 'tool';
}
