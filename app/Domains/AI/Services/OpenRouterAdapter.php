<?php

declare(strict_types=1);

namespace App\Domains\AI\Services;

use App\Domains\AI\Contracts\AgentLlmPort;
use App\Domains\AI\DTOs\LlmResponse;
use App\Domains\AI\Exceptions\LlmUnavailableException;

/**
 * Placeholder until Phase 8 (OpenRouter HTTP adapter).
 */
final class OpenRouterAdapter implements AgentLlmPort
{
    public function chat(
        string $apiKey,
        string $model,
        array $messages,
        array $tools = [],
        array $fallbackModels = [],
    ): LlmResponse {
        throw new LlmUnavailableException('AgentLlmPort not implemented until Phase 8.');
    }

    public function stream(
        string $apiKey,
        string $model,
        array $messages,
        array $tools,
        callable $onDelta,
    ): LlmResponse {
        throw new LlmUnavailableException('AgentLlmPort not implemented until Phase 8.');
    }
}
