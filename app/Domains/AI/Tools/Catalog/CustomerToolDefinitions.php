<?php

declare(strict_types=1);

namespace App\Domains\AI\Tools\Catalog;

use App\Domains\AI\DTOs\ToolDefinition;
use App\Domains\AI\Enums\ToolName;

final class CustomerToolDefinitions
{
    public static function for(ToolName $tool): ToolDefinition
    {
        return match ($tool) {
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
                    'properties' => ['external_id' => ['type' => 'string']],
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
            default => throw new \InvalidArgumentException("Not a customer/metrics tool [{$tool->value}]."),
        };
    }
}
