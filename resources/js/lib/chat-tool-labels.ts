const TOOL_LABELS: Record<string, string> = {
    list_orders: 'Looking up orders',
    get_order: 'Reading order details',
    update_order: 'Updating order',
    fulfill_order: 'Marking order fulfilled',
    refund_order: 'Processing refund',
    tag_order: 'Tagging order',
    cancel_order: 'Cancelling order',
    list_products: 'Looking up products',
    get_product: 'Reading product details',
    create_product: 'Creating product',
    update_product: 'Updating product',
    delete_product: 'Deleting product',
    update_inventory: 'Updating inventory',
    list_customers: 'Looking up customers',
    get_customer: 'Reading customer profile',
    tag_customer: 'Tagging customer',
    get_metrics: 'Checking store metrics',
};

export function toolLabel(toolName: string): string {
    return TOOL_LABELS[toolName] ?? `Running ${toolName.replaceAll('_', ' ')}`;
}
