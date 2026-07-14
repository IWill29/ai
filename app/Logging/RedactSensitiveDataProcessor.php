<?php

declare(strict_types=1);

namespace App\Logging;

use App\Support\SensitiveData;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

final class RedactSensitiveDataProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            message: SensitiveData::sanitizeMessage($record->message),
            context: SensitiveData::redactContext($record->context),
        );
    }
}
