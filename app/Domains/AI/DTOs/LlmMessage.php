<?php

declare(strict_types=1);

namespace App\Domains\AI\DTOs;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final readonly class LlmMessage
{
    /**
     * @param  array<int, LlmContentPart>  $contentParts
     * @param  array<int, ToolCall>  $toolCalls
     */
    public function __construct(
        public string $role,
        public ?string $content = null,
        public array $contentParts = [],
        public array $toolCalls = [],
        public ?string $toolCallId = null,
    ) {}
}
