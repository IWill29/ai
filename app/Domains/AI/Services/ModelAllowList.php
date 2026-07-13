<?php

declare(strict_types=1);

namespace App\Domains\AI\Services;

use App\Domains\AI\Exceptions\ModelNotAllowedException;

final class ModelAllowList
{
    /** @return array<int, string> */
    public function all(): array
    {
        return array_merge(
            config('openrouter.models.budget', []),
            config('openrouter.models.balanced', []),
            config('openrouter.models.premium', []),
        );
    }

    public function assertAllowed(string $model): void
    {
        if (! in_array($model, $this->all(), true)) {
            throw new ModelNotAllowedException("Model [{$model}] is not in the allow-list.");
        }
    }

    /** @return array<int, array{tier: string, models: array<int, string>}> */
    public function forFrontend(): array
    {
        return [
            ['tier' => 'Budget', 'models' => config('openrouter.models.budget', [])],
            ['tier' => 'Balanced', 'models' => config('openrouter.models.balanced', [])],
            ['tier' => 'Premium', 'models' => config('openrouter.models.premium', [])],
        ];
    }

    /** @return array<int, string> */
    public function fallbacksFor(string $model): array
    {
        /** @var array<string, array<int, string>> $fallbacks */
        $fallbacks = config('openrouter.fallbacks', []);

        return $fallbacks[$model] ?? [];
    }

    public function tierFor(string $model): ?string
    {
        if (in_array($model, config('openrouter.models.budget', []), true)) {
            return 'Budget';
        }

        if (in_array($model, config('openrouter.models.balanced', []), true)) {
            return 'Balanced';
        }

        if (in_array($model, config('openrouter.models.premium', []), true)) {
            return 'Premium';
        }

        return null;
    }
}
