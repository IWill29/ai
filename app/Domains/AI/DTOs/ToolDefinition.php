<?php

declare(strict_types=1);

namespace App\Domains\AI\DTOs;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final readonly class ToolDefinition
{
    public function __construct(
        public string $name,
        public string $description,
        /** @var array<string, mixed> */
        public array $parameters,
        public bool $isWrite,
    ) {}
}
