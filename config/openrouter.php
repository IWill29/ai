<?php

declare(strict_types=1);

return [
    'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
    'app_name' => env('OPENROUTER_APP_NAME', 'AgentStore'),
    'app_url' => env('OPENROUTER_APP_URL', env('APP_URL')),

    'max_iterations' => 10,
    'timeout' => 120,
    'default_model' => 'openai/gpt-4o-mini',

    'suggested_models' => [
        'openai/gpt-4o-mini',
        'anthropic/claude-3.5-haiku',
        'deepseek/deepseek-chat',
        'openai/gpt-4o',
        'anthropic/claude-3.5-sonnet',
        'anthropic/claude-3-opus',
    ],

    'models' => [
        'budget' => [
            'openai/gpt-4o-mini',
            'anthropic/claude-3.5-haiku',
            'deepseek/deepseek-chat',
        ],
        'balanced' => [
            'openai/gpt-4o',
            'anthropic/claude-3.5-sonnet',
        ],
        'premium' => [
            'anthropic/claude-3-opus',
            'openai/o1',
        ],
    ],

    'fallbacks' => [
        'openai/gpt-4o-mini' => ['anthropic/claude-3.5-haiku'],
        'openai/gpt-4o' => ['anthropic/claude-3.5-sonnet'],
        'anthropic/claude-3-opus' => ['openai/gpt-4o'],
    ],
];
