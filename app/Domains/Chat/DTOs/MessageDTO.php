<?php

declare(strict_types=1);

namespace App\Domains\Chat\DTOs;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final readonly class MessageDTO
{
    /**
     * @param  array<int, AttachmentDTO>  $attachments
     * @param  array<int, ActionStepDTO>  $actionSteps
     */
    public function __construct(
        public string $id,
        public string $role,
        public ?string $content,
        public ?string $model,
        public array $attachments = [],
        public array $actionSteps = [],
    ) {}
}
