import type { ChatStoreSyncState } from '@/components/chat/chat-store-sync-panel';

export type ChatStoreItem = {
    id: string;
    name: string;
    domain: string;
    status: string;
    lastSyncedAt: string | null;
};

export type ModelTier = {
    tier: string;
    models: string[];
};

export type ConversationSummary = App.Domains.Chat.DTOs.ConversationSummaryDTO;

export type ChatPageProps = {
    stores: ChatStoreItem[];
    hasStores: boolean;
    hasValidByok: boolean;
    modelTiers: ModelTier[];
    conversations: ConversationSummary[];
    activeStoreId: string | null;
    storeSync: ChatStoreSyncState | null;
    prefillPrompt?: string | null;
    activeConversationId?: string | null;
    initialMessages?: App.Domains.Chat.DTOs.MessageDTO[];
    defaultModel?: string | null;
};

export type LiveStep = {
    stepOrder: number;
    tool: string;
    arguments: Record<string, unknown>;
    isWrite: boolean;
    status: 'running' | 'done' | 'failed' | 'awaiting_confirmation';
    durationMs?: number;
    summary?: Record<string, unknown> | null;
};

export type ConfirmationPayload = {
    action_step_id: string;
    tool: string;
    arguments: Record<string, unknown>;
    description: string;
    image_previews?: string[];
    description_preview?: string | null;
};

export type AgentStreamStatus =
    | 'idle'
    | 'streaming'
    | 'awaiting_confirmation'
    | 'error'
    | 'done';

export type PendingAttachment = {
    id: string;
    previewUrl: string;
    filename: string;
};
