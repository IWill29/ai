<?php

declare(strict_types=1);

use App\Domains\AI\Support\OpenRouterModels;

return [
    'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
    'app_name' => env('OPENROUTER_APP_NAME', 'AgentStore'),
    'app_url' => env('OPENROUTER_APP_URL', env('APP_URL')),

    'max_iterations' => 10,
    'timeout' => 120,
    'embedding_model' => env('OPENROUTER_EMBEDDING_MODEL', 'openai/text-embedding-3-small'),
    'embedding_dimensions' => 1536,
    'embedding_timeout' => 30,
    'default_model' => OpenRouterModels::GPT_4O_MINI,

    'suggested_models' => OpenRouterModels::suggested(),
    'models' => OpenRouterModels::tiers(),
    'fallbacks' => OpenRouterModels::fallbacks(),
];
