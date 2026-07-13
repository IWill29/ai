<?php

declare(strict_types=1);

namespace App\Domains\AI\Tools;

use App\Domains\AI\DTOs\ToolDefinition;
use App\Domains\AI\Enums\ToolName;
use App\Domains\AI\Tools\Catalog\CustomerToolDefinitions;
use App\Domains\AI\Tools\Catalog\OrderToolDefinitions;
use App\Domains\AI\Tools\Catalog\ProductToolDefinitions;

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
            ToolName::ListOrders,
            ToolName::GetOrder,
            ToolName::UpdateOrder,
            ToolName::FulfillOrder,
            ToolName::RefundOrder,
            ToolName::TagOrder,
            ToolName::CancelOrder => OrderToolDefinitions::for($tool),
            ToolName::ListProducts,
            ToolName::GetProduct,
            ToolName::CreateProduct,
            ToolName::UpdateProduct,
            ToolName::DeleteProduct,
            ToolName::UpdateInventory => ProductToolDefinitions::for($tool),
            ToolName::ListCustomers,
            ToolName::GetCustomer,
            ToolName::TagCustomer,
            ToolName::GetMetrics => CustomerToolDefinitions::for($tool),
        };
    }
}
