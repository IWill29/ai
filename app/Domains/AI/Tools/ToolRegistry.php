<?php

declare(strict_types=1);

namespace App\Domains\AI\Tools;

use App\Domains\AI\DTOs\ToolDefinition;
use App\Domains\AI\Enums\ToolName;

final class ToolRegistry
{
    /** @return array<int, ToolDefinition> */
    public function all(): array
    {
        return array_map(
            fn (ToolName $tool) => $this->for($tool),
            ToolName::cases(),
        );
    }

    public function for(ToolName $tool): ToolDefinition
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
                    'properties' => [
                        'external_id' => ['type' => 'string'],
                    ],
                    'required' => ['external_id'],
                ],
                isWrite: false,
            ),
            ToolName::UpdateOrder => new ToolDefinition(
                name: $tool->value,
                description: 'Patch order fields — status, note, tags, shipping/tracking. Requires merchant confirmation.',
                parameters: [
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
                ],
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
                    'properties' => [
                        'external_id' => ['type' => 'string'],
                    ],
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
                    'properties' => [
                        'external_id' => ['type' => 'string'],
                    ],
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
            ToolName::ListCustomers => new ToolDefinition(
                name: $tool->value,
                description: 'List or search customers from the synced store mirror.',
                parameters: [
                    'type' => 'object',
                    'properties' => [
                        'search' => ['type' => 'string'],
                        'limit' => ['type' => 'integer', 'default' => 25],
                    ],
                ],
                isWrite: false,
            ),
            ToolName::GetCustomer => new ToolDefinition(
                name: $tool->value,
                description: 'Get customer details by external_id.',
                parameters: [
                    'type' => 'object',
                    'properties' => [
                        'external_id' => ['type' => 'string'],
                    ],
                    'required' => ['external_id'],
                ],
                isWrite: false,
            ),
            ToolName::TagCustomer => new ToolDefinition(
                name: $tool->value,
                description: 'Add tags to a customer. Requires merchant confirmation.',
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
            ToolName::GetMetrics => new ToolDefinition(
                name: $tool->value,
                description: 'Get store KPI metrics (revenue, orders, customers, unfulfilled, low stock) for a date range.',
                parameters: [
                    'type' => 'object',
                    'properties' => [
                        'from' => ['type' => 'string', 'format' => 'date'],
                        'to' => ['type' => 'string', 'format' => 'date'],
                    ],
                    'required' => ['from', 'to'],
                ],
                isWrite: false,
            ),
        };
    }
}
