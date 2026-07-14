import { router } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';
import StoreController from '@/actions/App/Http/Controllers/Stores/StoreController';
import type { ChatStoreSyncState } from '@/components/chat/chat-store-sync-panel';

type LiveSyncOverride = Pick<
    ChatStoreSyncState,
    'state' | 'entity' | 'error' | 'lastSyncedAt' | 'status'
>;

export function useSyncStatus(initial: ChatStoreSyncState | null) {
    const [liveSync, setLiveSync] = useState<LiveSyncOverride | null>(null);
    const [syncMessage, setSyncMessage] = useState<string | null>(null);

    const syncState =
        initial === null
            ? null
            : {
                  ...initial,
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
            state: ChatStoreSyncState['state'];
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
        if (syncState?.state !== 'syncing' || initial === null) {
            return;
        }

        const interval = window.setInterval(() => {
            void pollSyncStatus(initial.connectionId);
        }, 3000);

        return () => window.clearInterval(interval);
    }, [initial, pollSyncStatus, syncState?.state]);

    const triggerSync = useCallback(() => {
        if (initial === null) {
            return;
        }

        router.post(
            StoreController.sync.url(initial.connectionId),
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
                            lastSyncedAt: current?.lastSyncedAt ?? initial.lastSyncedAt,
                            status: current?.status ?? initial.status,
                        }));
                    }
                },
            },
        );
    }, [initial]);

    return {
        syncState,
        syncMessage,
        triggerSync,
    };
}
