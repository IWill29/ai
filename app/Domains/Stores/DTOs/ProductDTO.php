<?php

declare(strict_types=1);

namespace App\Domains\Stores\DTOs;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final readonly class ProductDTO
{
    /**
     * @param  array<int, VariantDTO>  $variants
     * @param  array<int, string>  $imageUrls
     */
    public function __construct(
        public string $externalId,
        public string $title,
        public ?string $description,
        public ?string $status,
        public ?string $handle,
        public array $variants,
        public array $imageUrls = [],
    ) {}
}
