<?php

declare(strict_types=1);

namespace App\Domains\Chat\DTOs;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final readonly class ConversationDTO
{
    public function __construct(
        public string $id,
        public string $accountId,
        public string $userId,
        public ?string $storeConnectionId,
        public ?string $title,
        public ?string $model,
    ) {}
}
