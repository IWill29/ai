<?php

declare(strict_types=1);

namespace App\Domains\AI\Tools\Catalog;

use App\Domains\AI\DTOs\ToolDefinition;
use App\Domains\AI\Enums\ToolName;

final class OrderToolDefinitions
{
    public static function for(ToolName $tool): ToolDefinition
    {
        return match ($tool) {
            ToolName::ListOrders => new ToolDefinition(
                name: $tool->value,
                description: 'List or search orders from the synced store mirror. Supports filters by status, date, min total.',
                parameters: [
                    'type' => 'object',
                    'properties' => [
                        'fulfillment_status' => ['type' => 'string'],
                        'financial_status' => ['type' => 'string'],
                        'placed_after' => ['type' => 'string', 'format' => 'date-time'],
                        'min_total' => ['type' => 'number', 'description' => 'Minimum order total in major currency units'],
                        'search' => ['type' => 'string'],
                        'limit' => ['type' => 'integer', 'default' => 25],
                    ],
                ],
                isWrite: false,
            ),
            ToolName::GetOrder => new ToolDefinition(
                name: $tool->value,
                description: 'Get full details for a single order by external_id.',
                parameters: [
                    'type' => 'object',
                    'properties' => ['external_id' => ['type' => 'string']],
                    'required' => ['external_id'],
                ],
                isWrite: false,
            ),
            ToolName::UpdateOrder => new ToolDefinition(
                name: $tool->value,
                description: 'Patch order fields — status, note, tags, shipping/tracking. Requires merchant confirmation.',
                parameters: self::updateOrderParameters(),
                isWrite: true,
            ),
            ToolName::FulfillOrder => new ToolDefinition(
                name: $tool->value,
                description: 'Mark an order as fulfilled. Requires merchant confirmation.',
                parameters: [
                    'type' => 'object',
                    'properties' => [
                        'external_id' => ['type' => 'string'],
                        'tracking_number' => ['type' => 'string'],
                    ],
                    'required' => ['external_id'],
                ],
                isWrite: true,
            ),
            ToolName::RefundOrder => new ToolDefinition(
                name: $tool->value,
                description: 'Refund an order fully or partially. Requires merchant confirmation.',
                parameters: [
                    'type' => 'object',
                    'properties' => [
                        'external_id' => ['type' => 'string'],
                        'amount' => ['type' => 'number', 'description' => 'Refund amount in major currency units; omit for full refund'],
                    ],
                    'required' => ['external_id'],
                ],
                isWrite: true,
            ),
            ToolName::TagOrder => new ToolDefinition(
                name: $tool->value,
                description: 'Add tags to an order. Requires merchant confirmation.',
                parameters: [
                    'type' => 'object',
                    'properties' => [
                        'external_id' => ['type' => 'string'],
                        'tags' => ['type' => 'array', 'items' => ['type' => 'string']],
                    ],
                    'required' => ['external_id', 'tags'],
                ],
                isWrite: true,
            ),
            ToolName::CancelOrder => new ToolDefinition(
                name: $tool->value,
                description: 'Cancel an order. Requires merchant confirmation.',
                parameters: [
                    'type' => 'object',
                    'properties' => [
                        'external_id' => ['type' => 'string'],
                        'reason' => ['type' => 'string'],
                    ],
                    'required' => ['external_id'],
                ],
                isWrite: true,
            ),
            default => throw new \InvalidArgumentException("Not an order tool [{$tool->value}]."),
        };
    }

    /** @return array<string, mixed> */
    private static function updateOrderParameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'external_id' => ['type' => 'string'],
                'status' => ['type' => 'string'],
                'note' => ['type' => 'string'],
                'tags' => ['type' => 'array', 'items' => ['type' => 'string']],
                'tracking_number' => ['type' => 'string'],
                'tracking_company' => ['type' => 'string'],
                'shipping_address' => [
                    'type' => 'object',
                    'properties' => [
                        'address1' => ['type' => 'string'],
                        'address2' => ['type' => 'string'],
                        'city' => ['type' => 'string'],
                        'province' => ['type' => 'string'],
                        'zip' => ['type' => 'string'],
                        'country' => ['type' => 'string'],
                    ],
                ],
            ],
            'required' => ['external_id'],
        ];
    }
}
