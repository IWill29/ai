<?php

declare(strict_types=1);

namespace App\Http\Support;

final class SseEmitter
{
    /** @param array<string, mixed> $data */
    public function emit(string $event, array $data): void
    {
        echo 'event: '.$event."\n";
        echo 'data: '.json_encode($data, JSON_THROW_ON_ERROR)."\n\n";

        if (ob_get_level() > 0) {
            ob_flush();
        }

        flush();
    }
}
