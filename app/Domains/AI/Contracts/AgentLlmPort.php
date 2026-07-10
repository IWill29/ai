<?php

declare(strict_types=1);

namespace App\Domains\AI\Contracts;

use App\Domains\AI\DTOs\LlmMessage;
use App\Domains\AI\DTOs\LlmResponse;
use App\Domains\AI\DTOs\ToolDefinition;

/**
 * BYOK OpenRouter boundary — chat completions, streaming, tool calling, multimodal (ADR 041).
 */
interface AgentLlmPort
{
    /**
     * @param  array<int, LlmMessage>  $messages
     * @param  array<int, ToolDefinition>  $tools
     * @param  array<int, string>  $fallbackModels
     */
    public function chat(
        string $apiKey,
        string $model,
        array $messages,
        array $tools = [],
        array $fallbackModels = [],
    ): LlmResponse;

    /**
     * Streaming variant — invokes $onDelta for each token/step chunk.
     *
     * @param  array<int, LlmMessage>  $messages
     * @param  array<int, ToolDefinition>  $tools
     * @param  callable(string): void  $onDelta
     */
    public function stream(
        string $apiKey,
        string $model,
        array $messages,
        array $tools,
        callable $onDelta,
    ): LlmResponse;
}
