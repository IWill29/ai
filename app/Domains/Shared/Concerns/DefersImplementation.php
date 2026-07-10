<?php

declare(strict_types=1);

namespace App\Domains\Shared\Concerns;

use BadMethodCallException;

trait DefersImplementation
{
    protected function notImplemented(string $feature): never
    {
        throw new BadMethodCallException("{$feature} not implemented yet.");
    }
}
