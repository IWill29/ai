<?php

declare(strict_types=1);

namespace App\Domains\AI\Services;

final class OpenRouterSseParser
{
    /**
     * @return array{
     *     content: string,
     *     toolCalls: array<int, mixed>,
     *     finishReason: ?string,
     *     promptTokens: ?int,
     *     completionTokens: ?int,
     *     model: string,
     * }
     */
    public function parse(object $body, string $model, callable $onDelta): array
    {
        $content = '';
        $toolCalls = [];
        $finishReason = null;
        $promptTokens = null;
        $completionTokens = null;
        $resolvedModel = $model;
        $buffer = '';

        while (! $body->eof()) {
            $buffer .= $body->read(1024);

            while (($newlinePos = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $newlinePos));
                $buffer = substr($buffer, $newlinePos + 1);

                if ($line === '' || ! str_starts_with($line, 'data:')) {
                    continue;
                }

                $payload = trim(substr($line, 5));

                if ($payload === '[DONE]') {
                    break 2;
                }

                $state = $this->applyChunk(
                    payload: $payload,
                    content: $content,
                    toolCalls: $toolCalls,
                    finishReason: $finishReason,
                    promptTokens: $promptTokens,
                    completionTokens: $completionTokens,
                    resolvedModel: $resolvedModel,
                    onDelta: $onDelta,
                );

                $content = $state['content'];
                $toolCalls = $state['toolCalls'];
                $finishReason = $state['finishReason'];
                $promptTokens = $state['promptTokens'];
                $completionTokens = $state['completionTokens'];
                $resolvedModel = $state['resolvedModel'];
            }
        }

        return [
            'content' => $content,
            'toolCalls' => $toolCalls,
            'finishReason' => $finishReason,
            'promptTokens' => $promptTokens,
            'completionTokens' => $completionTokens,
            'model' => $resolvedModel,
        ];
    }

    /**
     * @param  array<int, mixed>  $toolCalls
     * @return array{
     *     content: string,
     *     toolCalls: array<int, mixed>,
     *     finishReason: ?string,
     *     promptTokens: ?int,
     *     completionTokens: ?int,
     *     resolvedModel: string,
     * }
     */
    private function applyChunk(
        string $payload,
        string $content,
        array $toolCalls,
        ?string $finishReason,
        ?int $promptTokens,
        ?int $completionTokens,
        string $resolvedModel,
        callable $onDelta,
    ): array {
        /** @var array<string, mixed>|null $chunk */
        $chunk = json_decode($payload, true);

        if (! is_array($chunk)) {
            return compact('content', 'toolCalls', 'finishReason', 'promptTokens', 'completionTokens')
                + ['resolvedModel' => $resolvedModel];
        }

        if (isset($chunk['model']) && is_string($chunk['model'])) {
            $resolvedModel = $chunk['model'];
        }

        $choice = $chunk['choices'][0] ?? null;

        if (! is_array($choice)) {
            return compact('content', 'toolCalls', 'finishReason', 'promptTokens', 'completionTokens')
                + ['resolvedModel' => $resolvedModel];
        }

        $delta = $choice['delta'] ?? [];

        if (is_array($delta) && isset($delta['content']) && is_string($delta['content']) && $delta['content'] !== '') {
            $content .= $delta['content'];
            $onDelta($delta['content']);
        }

        if (is_array($delta) && isset($delta['tool_calls']) && is_array($delta['tool_calls'])) {
            $toolCalls = $this->mergeToolCallDeltas($toolCalls, $delta['tool_calls']);
        }

        if (isset($choice['finish_reason']) && is_string($choice['finish_reason'])) {
            $finishReason = $choice['finish_reason'];
        }

        if (isset($chunk['usage']) && is_array($chunk['usage'])) {
            $promptTokens = isset($chunk['usage']['prompt_tokens']) ? (int) $chunk['usage']['prompt_tokens'] : $promptTokens;
            $completionTokens = isset($chunk['usage']['completion_tokens']) ? (int) $chunk['usage']['completion_tokens'] : $completionTokens;
        }

        return compact('content', 'toolCalls', 'finishReason', 'promptTokens', 'completionTokens')
            + ['resolvedModel' => $resolvedModel];
    }

    /** @param array<int, mixed> $existing @param array<int, mixed> $deltas */
    private function mergeToolCallDeltas(array $existing, array $deltas): array
    {
        foreach ($deltas as $delta) {
            if (! is_array($delta)) {
                continue;
            }

            $index = $delta['index'] ?? count($existing);

            if (! isset($existing[$index])) {
                $existing[$index] = [
                    'id' => '',
                    'function' => ['name' => '', 'arguments' => ''],
                ];
            }

            if (isset($delta['id'])) {
                $existing[$index]['id'] = $delta['id'];
            }

            if (isset($delta['function']['name'])) {
                $existing[$index]['function']['name'] = $delta['function']['name'];
            }

            if (isset($delta['function']['arguments'])) {
                $existing[$index]['function']['arguments'] .= $delta['function']['arguments'];
            }
        }

        return array_values($existing);
    }
}
