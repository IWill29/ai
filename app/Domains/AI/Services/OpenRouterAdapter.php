<?php

declare(strict_types=1);

namespace App\Domains\AI\Services;

use App\Domains\AI\Contracts\AgentLlmPort;
use App\Domains\AI\DTOs\LlmContentPart;
use App\Domains\AI\DTOs\LlmMessage;
use App\Domains\AI\DTOs\LlmResponse;
use App\Domains\AI\DTOs\ToolCall;
use App\Domains\AI\DTOs\ToolDefinition;
use App\Domains\AI\Exceptions\InvalidApiKeyException;
use App\Domains\AI\Exceptions\LlmUnavailableException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final class OpenRouterAdapter implements AgentLlmPort
{
    public function __construct(
        private readonly OpenRouterSseParser $sseParser,
    ) {}

    public function chat(
        string $apiKey,
        string $model,
        array $messages,
        array $tools = [],
        array $fallbackModels = [],
        string $accountId = '',
    ): LlmResponse {
        $response = $this->request(
            apiKey: $apiKey,
            model: $model,
            messages: $messages,
            tools: $tools,
            fallbackModels: $fallbackModels,
            accountId: $accountId,
            stream: false,
        );

        return $this->parseResponse($response->json(), $model);
    }

    public function stream(
        string $apiKey,
        string $model,
        array $messages,
        array $tools,
        callable $onDelta,
        string $accountId = '',
        array $fallbackModels = [],
    ): LlmResponse {
        $response = $this->request(
            apiKey: $apiKey,
            model: $model,
            messages: $messages,
            tools: $tools,
            fallbackModels: $fallbackModels,
            accountId: $accountId,
            stream: true,
        );

        $parsed = $this->sseParser->parse(
            $response->toPsrResponse()->getBody(),
            $model,
            $onDelta,
        );

        return new LlmResponse(
            content: $parsed['content'] !== '' ? $parsed['content'] : null,
            toolCalls: $this->normalizeToolCalls($parsed['toolCalls']),
            finishReason: $parsed['finishReason'],
            promptTokens: $parsed['promptTokens'],
            completionTokens: $parsed['completionTokens'],
            model: $parsed['model'],
        );
    }

    /**
     * @param  array<int, LlmMessage>  $messages
     * @param  array<int, ToolDefinition>  $tools
     * @param  array<int, string>  $fallbackModels
     */
    private function request(
        string $apiKey,
        string $model,
        array $messages,
        array $tools,
        array $fallbackModels,
        string $accountId,
        bool $stream,
    ): Response {
        $body = [
            'model' => $model,
            'messages' => $this->serializeMessages($messages),
            'stream' => $stream,
        ];

        if ($accountId !== '') {
            $body['user'] = $accountId;
        }

        if ($tools !== []) {
            $body['tools'] = $this->serializeTools($tools);
            $body['tool_choice'] = 'auto';
        }

        if ($fallbackModels !== []) {
            $body['models'] = array_merge([$model], $fallbackModels);
            $body['route'] = 'fallback';
        }

        $response = Http::withToken($apiKey)
            ->withHeaders([
                'HTTP-Referer' => (string) config('openrouter.app_url'),
                'X-Title' => (string) config('openrouter.app_name'),
            ])
            ->timeout((int) config('openrouter.timeout', 120))
            ->withOptions($stream ? ['stream' => true] : [])
            ->post((string) config('openrouter.base_url').'/chat/completions', $body);

        if ($response->status() === 401) {
            throw new InvalidApiKeyException('OpenRouter rejected the API key.');
        }

        if ($response->failed()) {
            throw new LlmUnavailableException('OpenRouter HTTP '.$response->status().': '.$response->body());
        }

        return $response;
    }

    /** @param array<string, mixed>|null $payload */
    private function parseResponse(?array $payload, string $fallbackModel): LlmResponse
    {
        if ($payload === null) {
            throw new LlmUnavailableException('OpenRouter returned an empty response.');
        }

        $choice = $payload['choices'][0] ?? null;
        $message = is_array($choice) ? ($choice['message'] ?? []) : [];
        $toolCalls = [];

        if (is_array($message) && isset($message['tool_calls']) && is_array($message['tool_calls'])) {
            $toolCalls = $this->normalizeToolCalls($message['tool_calls']);
        }

        $usage = is_array($payload['usage'] ?? null) ? $payload['usage'] : [];

        return new LlmResponse(
            content: is_array($message) ? ($message['content'] ?? null) : null,
            toolCalls: $toolCalls,
            finishReason: is_array($choice) ? ($choice['finish_reason'] ?? null) : null,
            promptTokens: isset($usage['prompt_tokens']) ? (int) $usage['prompt_tokens'] : null,
            completionTokens: isset($usage['completion_tokens']) ? (int) $usage['completion_tokens'] : null,
            model: is_string($payload['model'] ?? null) ? $payload['model'] : $fallbackModel,
        );
    }

    /**
     * @param  array<int, LlmMessage>  $messages
     * @return array<int, array<string, mixed>>
     */
    private function serializeMessages(array $messages): array
    {
        return array_map(function (LlmMessage $message): array {
            if ($message->role === 'tool') {
                return [
                    'role' => 'tool',
                    'tool_call_id' => $message->toolCallId,
                    'content' => $message->content ?? '',
                ];
            }

            if ($message->contentParts !== []) {
                return [
                    'role' => $message->role,
                    'content' => array_map(
                        fn (LlmContentPart $part) => match ($part->type) {
                            'text' => ['type' => 'text', 'text' => $part->text ?? ''],
                            'image_url' => [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => $part->imageUrl,
                                    'detail' => $part->detail ?? 'auto',
                                ],
                            ],
                            default => throw new \InvalidArgumentException("Unknown content part [{$part->type}]."),
                        },
                        $message->contentParts,
                    ),
                ];
            }

            $payload = [
                'role' => $message->role,
                'content' => $message->content,
            ];

            if ($message->toolCalls !== []) {
                $payload['tool_calls'] = array_map(
                    fn (ToolCall $toolCall) => [
                        'id' => $toolCall->id,
                        'type' => 'function',
                        'function' => [
                            'name' => $toolCall->name,
                            'arguments' => json_encode($toolCall->arguments, JSON_THROW_ON_ERROR),
                        ],
                    ],
                    $message->toolCalls,
                );
            }

            return $payload;
        }, $messages);
    }

    /**
     * @param  array<int, ToolDefinition>  $tools
     * @return array<int, array<string, mixed>>
     */
    private function serializeTools(array $tools): array
    {
        return array_map(
            fn (ToolDefinition $tool) => [
                'type' => 'function',
                'function' => [
                    'name' => $tool->name,
                    'description' => $tool->description,
                    'parameters' => $tool->parameters,
                ],
            ],
            $tools,
        );
    }

    /**
     * @param  array<int, mixed>  $rawToolCalls
     * @return array<int, ToolCall>
     */
    private function normalizeToolCalls(array $rawToolCalls): array
    {
        $normalized = [];

        foreach ($rawToolCalls as $raw) {
            if ($raw instanceof ToolCall) {
                $normalized[] = $raw;

                continue;
            }

            if (! is_array($raw)) {
                continue;
            }

            $function = $raw['function'] ?? [];
            $arguments = [];

            if (is_array($function) && isset($function['arguments'])) {
                $decoded = json_decode((string) $function['arguments'], true);
                $arguments = is_array($decoded) ? $decoded : [];
            }

            $normalized[] = new ToolCall(
                id: (string) ($raw['id'] ?? ''),
                name: (string) (is_array($function) ? ($function['name'] ?? '') : ''),
                arguments: $arguments,
            );
        }

        return $normalized;
    }
}
