import { KeyRound, LayoutGrid, MessageSquare, RefreshCw, Sparkles, Store, TrendingUp } from 'lucide-react';
import { cn } from '@/lib/utils';

const miniPanel =
    'rounded-lg border border-border/50 bg-muted/25';

type MockupProps = Readonly<{
    className?: string;
}>;

export function ChatFeatureMockup({ className }: MockupProps) {
    return (
        <div
            aria-hidden
            className={cn(
                'relative flex min-h-[9.5rem] flex-col justify-end overflow-hidden rounded-xl border border-border/50 bg-gradient-to-b from-indigo-500/5 to-muted/20 p-3',
                className,
            )}
        >
            <div className="space-y-2">
                <div className="ml-auto max-w-[88%] rounded-xl rounded-tr-sm bg-indigo-500/15 px-2.5 py-1.5 text-[10px] ring-1 ring-indigo-500/20 sm:text-xs">
                    Fulfill order #1042
                </div>
                <div className="rounded-lg border border-emerald-500/20 bg-emerald-500/5 px-2 py-1 font-mono text-[9px] text-emerald-700 dark:text-emerald-300 sm:text-[10px]">
                    ✓ get_order #1042
                </div>
                <div className={cn(miniPanel, 'p-2 text-[10px] sm:text-xs')}>
                    <span className="font-medium">Confirm fulfill?</span>
                    <div className="mt-1.5 flex gap-1">
                        <span className="flex-1 rounded-md border border-border/60 py-0.5 text-center text-muted-foreground">
                            Cancel
                        </span>
                        <span className="flex-1 rounded-md bg-indigo-600 py-0.5 text-center text-white">
                            Confirm
                        </span>
                    </div>
                </div>
            </div>
        </div>
    );
}

export function DashboardFeatureMockup({ className }: MockupProps) {
    return (
        <div
            aria-hidden
            className={cn(
                'grid min-h-[9.5rem] grid-cols-2 gap-2 rounded-xl border border-border/50 bg-muted/15 p-3',
                className,
            )}
        >
            {[
                { label: 'Revenue', value: '$12.4k', icon: TrendingUp },
                { label: 'Orders', value: '156', icon: LayoutGrid },
                { label: 'Unfulfilled', value: '3', icon: MessageSquare, highlight: true },
                { label: 'Low stock', value: '2', icon: Store },
            ].map(({ label, value, icon: Icon, highlight }) => (
                <div
                    key={label}
                    className={cn(
                        miniPanel,
                        'flex flex-col justify-between p-2',
                        highlight && 'ring-1 ring-indigo-500/30',
                    )}
                >
                    <div className="flex items-center justify-between gap-1">
                        <span className="text-[9px] text-muted-foreground sm:text-[10px]">{label}</span>
                        <Icon className="size-3 text-indigo-500" />
                    </div>
                    <span className="text-sm font-semibold tracking-tight">{value}</span>
                </div>
            ))}
        </div>
    );
}

export function ShopifyFeatureMockup({ className }: MockupProps) {
    return (
        <div
            aria-hidden
            className={cn(
                'flex min-h-[9.5rem] flex-col justify-between rounded-xl border border-border/50 bg-muted/15 p-3',
                className,
            )}
        >
            <div className="flex items-center gap-2 text-xs font-medium">
                <Store className="size-4 shrink-0 text-indigo-500" />
                <span className="truncate">demo-store.myshopify.com</span>
            </div>
            <div className="space-y-2">
                <div className="h-2 overflow-hidden rounded-full bg-muted/60">
                    <div className="h-full w-full rounded-full bg-indigo-500/70" />
                </div>
                <div className="flex flex-wrap items-center justify-between gap-x-2 gap-y-1 text-[10px] text-muted-foreground sm:text-xs">
                    <span className="flex items-center gap-1 text-emerald-600 dark:text-emerald-400">
                        <RefreshCw className="size-3" />
                        Synced
                    </span>
                    <span className="truncate">Orders · Products · Customers</span>
                </div>
            </div>
        </div>
    );
}

export function ByokFeatureMockup({ className }: MockupProps) {
    return (
        <div
            aria-hidden
            className={cn(
                'flex min-h-[9.5rem] flex-col justify-between rounded-xl border border-border/50 bg-muted/15 p-3',
                className,
            )}
        >
            <div className="flex items-center gap-2 text-xs font-medium">
                <Sparkles className="size-4 text-indigo-500" />
                OpenRouter · BYOK
            </div>
            <div className="space-y-2">
                <div className="flex items-center gap-2 rounded-lg border border-border/60 bg-background/60 px-2.5 py-2 font-mono text-[10px] text-muted-foreground">
                    <KeyRound className="size-3.5 shrink-0" />
                    sk-or-v1-••••••••
                </div>
                <div className="flex flex-wrap gap-1.5">
                    {['GPT-4o mini', 'Sonnet', 'Opus'].map((model, index) => (
                        <span
                            key={model}
                            className={cn(
                                'rounded-md px-2 py-0.5 text-[9px] sm:text-[10px]',
                                index === 0
                                    ? 'bg-indigo-500/15 text-indigo-700 ring-1 ring-indigo-500/20 dark:text-indigo-300'
                                    : 'border border-border/60 text-muted-foreground',
                            )}
                        >
                            {model}
                        </span>
                    ))}
                </div>
            </div>
        </div>
    );
}
