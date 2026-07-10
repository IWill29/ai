<?php

declare(strict_types=1);

namespace App\Domains\AI\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum ToolName: string
{
    case ListOrders = 'list_orders';
    case GetOrder = 'get_order';
    case UpdateOrder = 'update_order';
    case FulfillOrder = 'fulfill_order';
    case RefundOrder = 'refund_order';
    case TagOrder = 'tag_order';
    case CancelOrder = 'cancel_order';
    case ListProducts = 'list_products';
    case GetProduct = 'get_product';
    case CreateProduct = 'create_product';
    case UpdateProduct = 'update_product';
    case DeleteProduct = 'delete_product';
    case UpdateInventory = 'update_inventory';
    case ListCustomers = 'list_customers';
    case GetCustomer = 'get_customer';
    case TagCustomer = 'tag_customer';
    case GetMetrics = 'get_metrics';

    public function isWrite(): bool
    {
        return match ($this) {
            self::UpdateOrder, self::FulfillOrder, self::RefundOrder,
            self::TagOrder, self::CancelOrder, self::CreateProduct,
            self::UpdateProduct, self::DeleteProduct, self::UpdateInventory,
            self::TagCustomer => true,
            self::ListOrders, self::GetOrder, self::ListProducts,
            self::GetProduct, self::ListCustomers, self::GetCustomer,
            self::GetMetrics => false,
        };
    }
}
