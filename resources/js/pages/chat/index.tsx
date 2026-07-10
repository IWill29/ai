import { Head, router } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';
import StoreController from '@/actions/App/Http/Controllers/Stores/StoreController';
import ChatEmptyState from '@/components/chat/chat-empty-state';
import ChatStoreSyncPanel from '@/components/chat/chat-store-sync-panel';
import type {ChatStoreSyncState} from '@/components/chat/chat-store-sync-panel';
import { index as chatIndex } from '@/routes/chat';

type StoreSyncProps = ChatStoreSyncState;

type ChatPageContentProps = {
    storeSync: StoreSyncProps | null;
};

type ChatIndexProps = ChatPageContentProps & {
    prefillPrompt?: string | null;
};

type LiveSyncOverride = Pick<
    StoreSyncProps,
    'state' | 'entity' | 'error' | 'lastSyncedAt' | 'status'
>;

function ChatPageContent({ storeSync }: ChatPageContentProps) {
    const [liveSync, setLiveSync] = useState<LiveSyncOverride | null>(null);
    const [syncMessage, setSyncMessage] = useState<string | null>(null);

    const syncState =
        storeSync === null
            ? null
            : {
                  ...storeSync,
                  ...(liveSync ?? {}),
              };

    const pollSyncStatus = useCallback(async (connectionId: string) => {
        const response = await fetch(`/stores/${connectionId}/sync-status`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            return;
        }

        const data = (await response.json()) as {
            state: StoreSyncProps['state'];
            entity: string | null;
            error: string | null;
            last_synced_at: string | null;
            status: string;
        };

        setLiveSync({
            state: data.state,
            entity: data.entity,
            error: data.error,
            lastSyncedAt: data.last_synced_at,
            status: data.status,
        });
    }, []);

    useEffect(() => {
        if (syncState?.state !== 'syncing') {
            return;
        }

        const interval = window.setInterval(() => {
            void pollSyncStatus(syncState.connectionId);
        }, 3000);

        return () => window.clearInterval(interval);
    }, [pollSyncStatus, syncState?.connectionId, syncState?.state]);

    const triggerSync = () => {
        if (syncState === null) {
            return;
        }

        router.post(
            StoreController.sync.url(syncState.connectionId),
            {},
            {
                preserveScroll: true,
                onSuccess: (page) => {
                    const flash = page.props.flash as { sync?: string } | undefined;
                    const message = flash?.sync;

                    if (message === 'already_syncing') {
                        setSyncMessage('Sync already in progress.');
                    } else if (message === 'started') {
                        setSyncMessage(null);
                        setLiveSync((current) => ({
                            state: 'syncing',
                            entity: current?.entity ?? null,
                            error: null,
                            lastSyncedAt: current?.lastSyncedAt ?? syncState.lastSyncedAt,
                            status: current?.status ?? syncState.status,
                        }));
                    }
                },
            },
        );
    };

    return (
        <>
            <Head title="Chat" />

            {syncState === null ? (
                <ChatEmptyState />
            ) : (
                <ChatStoreSyncPanel
                    syncState={syncState}
                    syncMessage={syncMessage}
                    onSync={triggerSync}
                />
            )}
        </>
    );
}

export default function ChatIndex(props: ChatIndexProps) {
    return (
        <ChatPageContent
            key={`${props.storeSync?.connectionId ?? 'no-store'}-${props.prefillPrompt ?? ''}`}
            storeSync={props.storeSync}
        />
    );
}

ChatIndex.layout = {
    breadcrumbs: [
        {
            title: 'Chat',
            href: chatIndex(),
        },
    ],
};
