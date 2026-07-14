<?php

declare(strict_types=1);

namespace App\Domains\Chat\DTOs;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final readonly class ConversationSummaryDTO
{
    public function __construct(
        public string $id,
        public ?string $title,
        public ?string $model,
        public ?string $storeConnectionId,
        public string $updatedAt,
    ) {}
}
