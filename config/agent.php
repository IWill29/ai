<?php

declare(strict_types=1);

return [
    'rate_limit' => [
        'per_minute' => 10,
        'attachments_per_minute' => 20,
    ],
    'max_tool_result_chars' => 8000,
    'attachment' => [
        'disk' => 'attachments',
        'max_files' => 5,
        'max_size_bytes' => 5 * 1024 * 1024,
        'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
        'ttl_hours' => 24,
    ],
];
