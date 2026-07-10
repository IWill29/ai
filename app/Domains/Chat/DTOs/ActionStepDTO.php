<?php

declare(strict_types=1);

namespace App\Domains\Chat\DTOs;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final readonly class ActionStepDTO
{
    public function __construct(
        public string $id,
        public int $stepOrder,
        public string $toolName,
        /** @var array<string, mixed> */
        public array $arguments,
        public ?string $targetPlatform,
        public string $status,
        public bool $isWrite,
        public ?bool $confirmed,
        /** @var array<string, mixed>|null */
        public ?array $resultSummary,
        public ?int $durationMs,
    ) {}
}
