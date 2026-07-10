<?php

declare(strict_types=1);

namespace App\Domains\AI\DTOs;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final readonly class LlmResponse
{
    /**
     * @param  array<int, ToolCall>  $toolCalls
     */
    public function __construct(
        public ?string $content,
        public array $toolCalls,
        public ?string $finishReason,
        public ?int $promptTokens,
        public ?int $completionTokens,
        public string $model,
    ) {}
}
