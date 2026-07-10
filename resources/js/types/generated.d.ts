declare namespace App {
namespace Domains {
namespace AI {
namespace DTOs {
export type LlmContentPart = {
readonly type: string,
readonly text: string | null,
readonly imageUrl: string | null,
readonly detail: string | null,
};
export type LlmMessage = {
readonly role: string,
readonly content: string | null,
readonly contentParts: App.Domains.AI.DTOs.LlmContentPart[],
readonly toolCalls: App.Domains.AI.DTOs.ToolCall[],
readonly toolCallId: string | null,
};
export type LlmResponse = {
readonly content: string | null,
readonly toolCalls: App.Domains.AI.DTOs.ToolCall[],
readonly finishReason: string | null,
readonly promptTokens: number | null,
readonly completionTokens: number | null,
readonly model: string,
};
export type ToolCall = {
readonly id: string,
readonly name: string,
readonly arguments: Record<string, any>,
};
export type ToolDefinition = {
readonly name: string,
readonly description: string,
readonly parameters: Record<string, any>,
readonly isWrite: boolean,
};
}
namespace Enums {
export type StepStatus = 'pending' | 'awaiting_confirmation' | 'running' | 'done' | 'failed';
export type ToolName = 'list_orders' | 'get_order' | 'update_order' | 'fulfill_order' | 'refund_order' | 'tag_order' | 'cancel_order' | 'list_products' | 'get_product' | 'create_product' | 'update_product' | 'delete_product' | 'update_inventory' | 'list_customers' | 'get_customer' | 'tag_customer' | 'get_metrics';
}
}
namespace Billing {
namespace DTOs {
export type PlanDTO = {
readonly slug: string,
readonly name: string,
readonly priceCents: number,
readonly currency: string,
readonly storeLimit: number | null,
readonly monthlyMessageLimit: number | null,
};
}
}
namespace Chat {
namespace DTOs {
export type ActionStepDTO = {
readonly id: string,
readonly stepOrder: number,
readonly toolName: string,
readonly arguments: Record<string, any>,
readonly targetPlatform: string | null,
readonly status: string,
readonly isWrite: boolean,
readonly confirmed: boolean | null,
readonly resultSummary: Record<string, any> | null,
readonly durationMs: number | null,
};
export type AttachmentDTO = {
readonly id: string,
readonly filename: string,
readonly mimeType: string,
readonly sizeBytes: number,
readonly previewUrl: string,
readonly status: string,
};
export type ConversationDTO = {
readonly id: string,
readonly accountId: string,
readonly userId: string,
readonly storeConnectionId: string | null,
readonly title: string | null,
readonly model: string | null,
};
export type MessageDTO = {
readonly id: string,
readonly role: string,
readonly content: string | null,
readonly model: string | null,
readonly attachments: App.Domains.Chat.DTOs.AttachmentDTO[],
readonly actionSteps: App.Domains.Chat.DTOs.ActionStepDTO[],
};
}
namespace Enums {
export type MessageRole = 'user' | 'assistant' | 'system' | 'tool';
}
}
namespace Stores {
namespace DTOs {
export type CustomerDTO = {
readonly externalId: string,
readonly email: string | null,
readonly name: string | null,
readonly ordersCount: number,
readonly totalSpentMinor: number,
readonly currency: string | null,
};
export type LineItemDTO = {
readonly externalId: string,
readonly title: string,
readonly quantity: number,
readonly priceMinor: number,
readonly currency: string | null,
};
export type MetricDTO = {
readonly revenueMinor: number,
readonly ordersCount: number,
readonly averageOrderValueMinor: number,
readonly newCustomers: number,
readonly returningCustomers: number,
readonly unfulfilledOrders: number,
readonly lowStockProducts: number,
readonly currency: string,
};
export type OrderDTO = {
readonly externalId: string,
readonly orderNumber: string | null,
readonly financialStatus: string | null,
readonly fulfillmentStatus: string | null,
readonly totalPriceMinor: number,
readonly currency: string | null,
readonly customerExternalId: string | null,
readonly lineItems: App.Domains.Stores.DTOs.LineItemDTO[],
readonly placedAt: undefined | null,
};
export type OrderQuery = {
readonly fulfillmentStatus: string | null,
readonly financialStatus: string | null,
readonly placedAfter: undefined | null,
readonly minTotalMinor: number | null,
readonly search: string | null,
readonly limit: number,
readonly cursor: string | null,
};
export type PaginatedResult = {
readonly items: any[],
readonly nextCursor: string | null,
readonly hasMore: boolean,
};
export type ProductDTO = {
readonly externalId: string,
readonly title: string,
readonly description: string | null,
readonly status: string | null,
readonly handle: string | null,
readonly variants: App.Domains.Stores.DTOs.VariantDTO[],
readonly imageUrls: string[],
};
export type ProductImageInput = {
readonly localPath: string,
readonly mimeType: string,
readonly filename: string,
};
export type VariantDTO = {
readonly externalId: string,
readonly sku: string | null,
readonly title: string | null,
readonly priceMinor: number | null,
readonly currency: string | null,
readonly inventoryQuantity: number | null,
};
}
namespace Enums {
export type Platform = 'shopify' | 'woocommerce';
}
}
}
}
