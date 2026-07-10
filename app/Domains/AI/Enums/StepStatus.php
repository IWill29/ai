<?php

declare(strict_types=1);

namespace App\Domains\AI\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum StepStatus: string
{
    case Pending = 'pending';
    case AwaitingConfirmation = 'awaiting_confirmation';
    case Running = 'running';
    case Done = 'done';
    case Failed = 'failed';
}
