import StoreController from '@/actions/App/Http/Controllers/Stores/StoreController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { connect } from '@/routes/stores';
import { Head, Link, router } from '@inertiajs/react';
import { AlertTriangle, Loader2, RefreshCw, Store } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';

type StoreSyncProps = {
    connectionId: string;
    storeName: string;
    state: 'idle' | 'syncing' | 'failed';
    entity: string | null;
    error: string | null;
    lastSyncedAt: string | null;
    status: string;
};

type Props = {
    storeSync: StoreSyncProps | null;
};

function formatDateTime(value: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

function entityLabel(entity: string | null): string {
    if (entity === null) {
        return '';
    }

    return entity.charAt(0).toUpperCase() + entity.slice(1);
}

export default function ChatIndex({ storeSync }: Props) {
    const [syncState, setSyncState] = useState(storeSync);
    const [syncMessage, setSyncMessage] = useState<string | null>(null);

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

        setSyncState((current) => {
            if (current === null) {
                return current;
            }

            return {
                ...current,
                state: data.state,
                entity: data.entity,
                error: data.error,
                lastSyncedAt: data.last_synced_at,
                status: data.status,
            };
        });
    }, []);

    useEffect(() => {
        setSyncState(storeSync);
    }, [storeSync]);

    useEffect(() => {
        if (syncState?.state !== 'syncing' || syncState.connectionId === undefined) {
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
                        setSyncState((current) =>
                            current === null ? current : { ...current, state: 'syncing' },
                        );
                    }
                },
            },
        );
    };

    return (
        <>
            <Head title="Chat" />

            <div className="flex h-full flex-1 gap-4 p-4">
                <aside className="flex w-72 shrink-0 flex-col gap-4">
                    <Card className="rounded-2xl border-border/60 shadow-[0_4px_20px_-2px_rgb(0_0_0/0.08)]">
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base">Store</CardTitle>
                            <CardDescription>Sync mirror data for AI chat.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {syncState === null ? (
                                <div className="space-y-3">
                                    <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                        <Store className="size-4" />
                                        No store connected
                                    </div>
                                    <Button asChild className="w-full rounded-xl bg-indigo-600 hover:bg-indigo-500">
                                        <Link href={connect()}>Connect store</Link>
                                    </Button>
                                </div>
                            ) : (
                                <>
                                    <div className="space-y-1">
                                        <p className="font-medium text-foreground">{syncState.storeName}</p>
                                        <Badge variant="secondary" className="rounded-lg capitalize">
                                            {syncState.status.replace('_', ' ')}
                                        </Badge>
                                    </div>

                                    {syncState.status === 'webhooks_pending' && (
                                        <div className="flex gap-2 rounded-xl border border-amber-500/20 bg-amber-500/10 px-3 py-2 text-xs text-amber-800 dark:text-amber-200">
                                            <AlertTriangle className="mt-0.5 size-4 shrink-0" />
                                            Webhooks pending — real-time updates may be delayed.
                                        </div>
                                    )}

                                    {syncState.state === 'failed' && syncState.error !== null && (
                                        <div className="rounded-xl border border-destructive/20 bg-destructive/10 px-3 py-2 text-xs text-destructive">
                                            {syncState.error}
                                        </div>
                                    )}

                                    <Button
                                        onClick={triggerSync}
                                        disabled={syncState.state === 'syncing'}
                                        className="w-full rounded-xl bg-indigo-600 hover:bg-indigo-500"
                                    >
                                        {syncState.state === 'syncing' ? (
                                            <>
                                                <Loader2 className="mr-2 size-4 animate-spin" />
                                                Syncing {entityLabel(syncState.entity)}…
                                            </>
                                        ) : (
                                            <>
                                                <RefreshCw className="mr-2 size-4" />
                                                Sync now
                                            </>
                                        )}
                                    </Button>

                                    {syncState.lastSyncedAt !== null && (
                                        <p className="text-xs text-muted-foreground">
                                            Last synced: {formatDateTime(syncState.lastSyncedAt)}
                                        </p>
                                    )}

                                    {syncMessage !== null && (
                                        <p className="text-xs text-muted-foreground">{syncMessage}</p>
                                    )}
                                </>
                            )}
                        </CardContent>
                    </Card>
                </aside>

                <div className="flex flex-1 flex-col gap-4 rounded-xl border border-border/60 bg-card/50 p-6">
                    <p className="text-muted-foreground">AI chat — coming in Phase 10.</p>
                </div>
            </div>
        </>
    );
}
