import { Link, router } from '@inertiajs/react';
import { LayoutDashboard, Plus } from 'lucide-react';
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
import { dashboard } from '@/routes';
import { createConversation } from '@/lib/chat-api';
import { loadLastModel } from '@/lib/chat-model-storage';

type Props = Readonly<{
    stores: ChatStoreItem[];
    activeStoreId: string | null;
    syncState: ChatStoreSyncState | null;
    syncMessage: string | null;
    onSync: () => void;
    defaultModel: string | null;
    onNewChat?: () => void;
}>;

export default function ChatLeftSidebar({
    stores,
    activeStoreId,
    syncState,
    syncMessage,
    onSync,
    defaultModel,
    onNewChat,
}: Props) {
    const handleStoreChange = (storeId: string) => {
        router.get('/chat', { store_id: storeId }, { preserveState: false });
    };

    const handleNewChat = async () => {
        if (onNewChat) {
            onNewChat();

            return;
        }

        if (activeStoreId === null) {
            return;
        }

        const model = loadLastModel(defaultModel ?? 'openai/gpt-4o-mini');
        const conversation = await createConversation(activeStoreId, model);
        router.get(`/chat/${conversation.id}`);
    };

    return (
        <div className="flex h-full flex-col gap-4 p-4">
            <Button
                type="button"
                variant="brand"
                className="w-full rounded-xl active:scale-[0.97] motion-reduce:active:scale-100 transition-transform duration-150"
                style={{ transitionTimingFunction: 'var(--ease-out-strong)' }}
                onClick={() => void handleNewChat()}
            >
                <Plus className="mr-2 size-4" aria-hidden />
                New chat
            </Button>

            {stores.length > 0 && activeStoreId !== null && (
                <div className="space-y-1.5">
                    <p className="text-xs font-medium text-muted-foreground">Store</p>
                    <Select value={activeStoreId} onValueChange={handleStoreChange}>
                        <SelectTrigger className="w-full rounded-xl">
                            <SelectValue placeholder="Select store" />
                        </SelectTrigger>
                        <SelectContent className="rounded-xl">
                            {stores.map((store) => (
                                <SelectItem key={store.id} value={store.id}>
                                    {store.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            )}

            {syncState !== null && (
                <ChatSyncControls
                    syncState={syncState}
                    syncMessage={syncMessage}
                    onSync={onSync}
                />
            )}

            <div className="flex-1" />

            <Link
                href={dashboard()}
                className="inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors duration-150 hover:text-foreground"
            >
                <LayoutDashboard className="size-3.5" aria-hidden />
                Dashboard
            </Link>
        </div>
    );
}
