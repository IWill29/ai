import type { ReactNode } from 'react';
import { useState } from 'react';
import ChatHistoryPanel from '@/components/chat/chat-history-panel';
import ChatLeftSidebar from '@/components/chat/chat-left-sidebar';
import ChatMobileHeader from '@/components/chat/chat-mobile-header';
import { Sheet, SheetContent } from '@/components/ui/sheet';
import type { ChatPageProps } from '@/types/chat';
import { useSyncStatus } from '@/hooks/use-sync-status';

type Props = Readonly<
    ChatPageProps & {
        children: ReactNode;
        activeConversationId?: string | null;
        onNewChat?: () => void;
    }
>;

export default function ChatLayout({
    children,
    stores,
    activeStoreId,
    storeSync,
    conversations,
    defaultModel,
    activeConversationId,
    onNewChat,
}: Props) {
    const [historyOpen, setHistoryOpen] = useState(false);
    const { syncState, syncMessage, triggerSync } = useSyncStatus(storeSync);

    return (
        <div className="-mx-4 flex h-[calc(100dvh-3.5rem)] min-h-0 flex-col overflow-hidden md:-mx-5">
            <ChatMobileHeader
                stores={stores}
                activeStoreId={activeStoreId}
                syncState={syncState}
                syncMessage={syncMessage}
                onSync={triggerSync}
                defaultModel={defaultModel ?? null}
                onOpenHistory={() => setHistoryOpen(true)}
            />

            <div className="flex min-h-0 flex-1">
                <aside className="hidden w-[var(--chat-sidebar-width)] shrink-0 border-r border-border/60 lg:block">
                    <ChatLeftSidebar
                        stores={stores}
                        activeStoreId={activeStoreId}
                        syncState={syncState}
                        syncMessage={syncMessage}
                        onSync={triggerSync}
                        defaultModel={defaultModel ?? null}
                        onNewChat={onNewChat}
                    />
                </aside>

                <main className="flex min-w-0 flex-1 flex-col">{children}</main>

                <aside className="hidden w-[var(--chat-history-width)] shrink-0 border-l border-border/60 lg:block">
                    <ChatHistoryPanel
                        conversations={conversations}
                        activeConversationId={activeConversationId}
                    />
                </aside>
            </div>

            <Sheet open={historyOpen} onOpenChange={setHistoryOpen}>
                <SheetContent
                    side="right"
                    className="w-[min(100vw,var(--chat-history-width))] p-0 motion-safe:duration-300"
                    style={{ transitionTimingFunction: 'var(--ease-drawer)' }}
                >
                    <ChatHistoryPanel
                        conversations={conversations}
                        activeConversationId={activeConversationId}
                        onSelect={() => setHistoryOpen(false)}
                        className="pt-10"
                    />
                </SheetContent>
            </Sheet>
        </div>
    );
}
