import { AlertTriangle, Loader2, RefreshCw, Store } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';

export type ChatStoreSyncState = {
    connectionId: string;
    storeName: string;
    state: 'idle' | 'syncing' | 'failed';
    entity: string | null;
    error: string | null;
    lastSyncedAt: string | null;
    status: string;
};

const cardClass =
    'rounded-2xl border-border/60 bg-card shadow-[0_4px_20px_-2px_rgb(0_0_0/0.08)] dark:border-border/80 dark:shadow-[0_8px_32px_-8px_rgb(0_0_0/0.55)]';

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

type Props = {
    syncState: ChatStoreSyncState;
    syncMessage: string | null;
    onSync: () => void;
};

export default function ChatStoreSyncPanel({ syncState, syncMessage, onSync }: Props) {
    return (
        <div className="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4 md:p-6">
            <p className="max-w-xl text-sm leading-relaxed text-muted-foreground">
                Your store mirror powers AI answers. Keep data fresh with Sync now before you ask
                about orders, inventory, or trends.
            </p>

            <Card className={cn(cardClass, 'relative overflow-hidden')}>
                <div
                    aria-hidden
                    className="pointer-events-none absolute inset-x-0 top-0 h-20 bg-[radial-gradient(ellipse_at_top,rgba(99,102,241,0.08),transparent_70%)] dark:bg-[radial-gradient(ellipse_at_top,rgba(99,102,241,0.14),transparent_72%)]"
                />
                <CardHeader className="relative pb-3">
                    <div className="flex items-start gap-3">
                        <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-indigo-500/10">
                            <Store
                                className="size-5 text-indigo-600 dark:text-indigo-400"
                                strokeWidth={2}
                                aria-hidden
                            />
                        </div>
                        <div className="space-y-1">
                            <CardTitle className="text-lg">{syncState.storeName}</CardTitle>
                            <CardDescription>Synced mirror for AI chat</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent className="relative space-y-4">
                    <Badge variant="secondary" className="rounded-lg capitalize">
                        {syncState.status.replace('_', ' ')}
                    </Badge>

                    {syncState.status === 'webhooks_pending' && (
                        <div className="flex gap-2 rounded-xl border border-amber-500/20 bg-amber-500/10 px-3 py-2 text-xs text-amber-800 dark:text-amber-200">
                            <AlertTriangle className="mt-0.5 size-4 shrink-0" aria-hidden />
                            Webhooks pending — real-time updates may be delayed.
                        </div>
                    )}

                    {syncState.state === 'failed' && syncState.error !== null && (
                        <div className="rounded-xl border border-destructive/20 bg-destructive/10 px-3 py-2 text-xs text-destructive">
                            {syncState.error}
                        </div>
                    )}

                    <Button
                        onClick={onSync}
                        disabled={syncState.state === 'syncing'}
                        variant="brand"
                        className="w-full rounded-full"
                    >
                        {syncState.state === 'syncing' ? (
                            <>
                                <Loader2 className="mr-2 size-4 animate-spin" aria-hidden />
                                Syncing {entityLabel(syncState.entity)}…
                            </>
                        ) : (
                            <>
                                <RefreshCw className="mr-2 size-4" aria-hidden />
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
                </CardContent>
            </Card>
        </div>
    );
}
