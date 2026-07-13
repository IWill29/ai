<?php

declare(strict_types=1);

namespace App\Domains\AI\Tools\Catalog;

use App\Domains\AI\DTOs\ToolDefinition;
use App\Domains\AI\Enums\ToolName;

final class ProductToolDefinitions
{
    public static function for(ToolName $tool): ToolDefinition
    {
        return match ($tool) {
            ToolName::ListProducts => new ToolDefinition(
                name: $tool->value,
                description: 'List or search products from the synced store mirror.',
                parameters: [
                    'type' => 'object',
                    'properties' => [
                        'search' => ['type' => 'string'],
                        'status' => ['type' => 'string'],
                        'limit' => ['type' => 'integer', 'default' => 25],
                    ],
                ],
                isWrite: false,
            ),
            ToolName::GetProduct => new ToolDefinition(
                name: $tool->value,
                description: 'Get full product details including description and variants by external_id.',
                parameters: [
                    'type' => 'object',
                    'properties' => ['external_id' => ['type' => 'string']],
                    'required' => ['external_id'],
                ],
                isWrite: false,
            ),
            ToolName::CreateProduct => new ToolDefinition(
                name: $tool->value,
                description: 'Create a new product. Requires merchant confirmation.',
                parameters: [
                    'type' => 'object',
                    'properties' => [
                        'title' => ['type' => 'string'],
                        'description' => ['type' => 'string'],
                        'status' => ['type' => 'string', 'enum' => ['ACTIVE', 'DRAFT']],
                    ],
                    'required' => ['title'],
                ],
                isWrite: true,
            ),
            ToolName::UpdateProduct => new ToolDefinition(
                name: $tool->value,
                description: 'Update product title, description, status, and/or attach chat-uploaded images. No price changes. Requires confirmation.',
                parameters: [
                    'type' => 'object',
                    'properties' => [
                        'external_id' => ['type' => 'string'],
                        'title' => ['type' => 'string'],
                        'description' => ['type' => 'string'],
                        'status' => ['type' => 'string', 'enum' => ['ACTIVE', 'DRAFT', 'ARCHIVED']],
                        'image_attachment_ids' => [
                            'type' => 'array',
                            'items' => ['type' => 'string', 'format' => 'uuid'],
                            'description' => 'IDs from merchant chat attachments. Max 5.',
                            'maxItems' => 5,
                        ],
                    ],
                    'required' => ['external_id'],
                ],
                isWrite: true,
            ),
            ToolName::DeleteProduct => new ToolDefinition(
                name: $tool->value,
                description: 'Delete a product. Requires merchant confirmation.',
                parameters: [
                    'type' => 'object',
                    'properties' => ['external_id' => ['type' => 'string']],
                    'required' => ['external_id'],
                ],
                isWrite: true,
            ),
            ToolName::UpdateInventory => new ToolDefinition(
                name: $tool->value,
                description: 'Update variant inventory quantity. Requires merchant confirmation.',
                parameters: [
                    'type' => 'object',
                    'properties' => [
                        'variant_external_id' => ['type' => 'string'],
                        'quantity' => ['type' => 'integer'],
                    ],
                    'required' => ['variant_external_id', 'quantity'],
                ],
                isWrite: true,
            ),
            default => throw new \InvalidArgumentException("Not a product tool [{$tool->value}]."),
        };
    }
}
