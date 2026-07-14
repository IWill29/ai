import { Menu, Plus } from 'lucide-react';
import { router } from '@inertiajs/react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import ChatSyncControls from '@/components/chat/chat-sync-controls';
import type { ChatStoreItem } from '@/types/chat';
import type { ChatStoreSyncState } from '@/components/chat/chat-store-sync-panel';
import { createConversation } from '@/lib/chat-api';
import { loadLastModel } from '@/lib/chat-model-storage';

type Props = Readonly<{
    stores: ChatStoreItem[];
    activeStoreId: string | null;
    syncState: ChatStoreSyncState | null;
    syncMessage: string | null;
    onSync: () => void;
    defaultModel: string | null;
    onOpenHistory: () => void;
}>;

export default function ChatMobileHeader({
    stores,
    activeStoreId,
    syncState,
    syncMessage,
    onSync,
    defaultModel,
    onOpenHistory,
}: Props) {
    const handleStoreChange = (storeId: string) => {
        router.get('/chat', { store_id: storeId }, { preserveState: false });
    };

    const handleNewChat = async () => {
        if (activeStoreId === null) {
            return;
        }

        const model = loadLastModel(defaultModel ?? 'openai/gpt-4o-mini');
        const conversation = await createConversation(activeStoreId, model);
        router.get(`/chat/${conversation.id}`);
    };

    return (
        <header className="flex shrink-0 items-center gap-2 border-b border-border/60 px-3 py-2 lg:hidden">
            <Button
                type="button"
                variant="ghost"
                size="icon"
                className="size-8 rounded-lg active:scale-[0.97]"
                onClick={onOpenHistory}
                aria-label="Open chat history"
            >
                <Menu className="size-4" aria-hidden />
            </Button>

            {stores.length > 0 && activeStoreId !== null && (
                <Select value={activeStoreId} onValueChange={handleStoreChange}>
                    <SelectTrigger className="h-8 flex-1 rounded-xl">
                        <SelectValue placeholder="Store" />
                    </SelectTrigger>
                    <SelectContent className="rounded-xl">
                        {stores.map((store) => (
                            <SelectItem key={store.id} value={store.id}>
                                {store.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            )}

            {syncState !== null && (
                <ChatSyncControls
                    syncState={syncState}
                    syncMessage={syncMessage}
                    onSync={onSync}
                    compact
                />
            )}

            <Button
                type="button"
                variant="ghost"
                size="icon"
                className="size-8 rounded-lg active:scale-[0.97]"
                onClick={() => void handleNewChat()}
                aria-label="New chat"
            >
                <Plus className="size-4" aria-hidden />
            </Button>
        </header>
    );
}
