import { Loader2, RefreshCw } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { formatDateTime } from '@/lib/format-datetime';
import { cn } from '@/lib/utils';

type Props = Readonly<{
    syncState: {
        state: 'idle' | 'syncing' | 'failed';
        entity: string | null;
        lastSyncedAt: string | null;
        error: string | null;
    };
    syncMessage: string | null;
    onSync: () => void;
    compact?: boolean;
}>;

function entityLabel(entity: string | null): string {
    if (entity === null) {
        return '';
    }

    return entity.charAt(0).toUpperCase() + entity.slice(1);
}

export default function ChatSyncControls({
    syncState,
    syncMessage,
    onSync,
    compact = false,
}: Props) {
    return (
        <div className={cn('space-y-1', compact && 'space-y-0')}>
            <Button
                type="button"
                variant={compact ? 'outline' : 'outline'}
                size={compact ? 'sm' : 'default'}
                className={cn(
                    'rounded-xl active:scale-[0.97] motion-reduce:active:scale-100',
                    'transition-transform duration-150',
                    compact ? 'h-8 px-3' : 'w-full',
                )}
                style={{ transitionTimingFunction: 'var(--ease-out-strong)' }}
                disabled={syncState.state === 'syncing'}
                onClick={onSync}
            >
                {syncState.state === 'syncing' ? (
                    <>
                        <Loader2 className="mr-1.5 size-3.5 animate-spin" aria-hidden />
                        {compact ? 'Syncing…' : `Syncing ${entityLabel(syncState.entity)}…`}
                    </>
                ) : (
                    <>
                        <RefreshCw className="mr-1.5 size-3.5" aria-hidden />
                        Sync now
                    </>
                )}
            </Button>

            {!compact && syncState.lastSyncedAt !== null && (
                <p className="text-xs text-muted-foreground">
                    Last synced: {formatDateTime(syncState.lastSyncedAt)}
                </p>
            )}

            {!compact && syncState.state === 'failed' && syncState.error !== null && (
                <p className="text-xs text-destructive">{syncState.error}</p>
            )}

            {!compact && syncMessage !== null && (
                <p className="text-xs text-muted-foreground">{syncMessage}</p>
            )}
        </div>
    );
}
