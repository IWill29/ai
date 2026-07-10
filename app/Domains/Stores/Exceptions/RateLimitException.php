<?php

declare(strict_types=1);

namespace App\Domains\Stores\Exceptions;

final class RateLimitException extends StoreException
{
    public function __construct(
        public readonly ?int $retryAfterSeconds = null,
        string $message = 'Store API rate limit reached',
    ) {
        parent::__construct($message);
    }
}
