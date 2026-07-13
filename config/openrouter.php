<?php

declare(strict_types=1);

use App\Domains\AI\Support\OpenRouterModels;

return [
    'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
    'app_name' => env('OPENROUTER_APP_NAME', 'AgentStore'),
    'app_url' => env('OPENROUTER_APP_URL', env('APP_URL')),

    'max_iterations' => 10,
    'timeout' => 120,
    'default_model' => OpenRouterModels::GPT_4O_MINI,

    'suggested_models' => OpenRouterModels::suggested(),
    'models' => OpenRouterModels::tiers(),
    'fallbacks' => OpenRouterModels::fallbacks(),
];
