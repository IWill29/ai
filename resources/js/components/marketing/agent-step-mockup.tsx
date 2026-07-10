import { Check, Link2, Loader2, RefreshCw, ShieldCheck, Store } from 'lucide-react';
import { cn } from '@/lib/utils';

export type AgentStepKind = 'setup' | 'chat' | 'read' | 'trace' | 'write';

const frameClass =
    'overflow-hidden rounded-2xl border border-border/60 bg-card/95 shadow-[0_16px_48px_-12px_rgb(0_0_0/0.35)]';

const panelClass =
    'rounded-xl border border-border/50 bg-muted/20 p-3 text-xs sm:text-sm';

type Props = {
    kind: AgentStepKind;
    className?: string;
};

function WindowChrome({ title }: { title: string }) {
    return (
        <div className="flex items-center gap-1.5 border-b border-border/50 bg-muted/25 px-3 py-2">
            <span className="size-2 rounded-full bg-red-400/70" />
            <span className="size-2 rounded-full bg-amber-400/70" />
            <span className="size-2 rounded-full bg-emerald-400/70" />
            <span className="ml-1 truncate text-[10px] text-muted-foreground sm:text-xs">{title}</span>
        </div>
    );
}

function SetupMockup() {
    return (
        <div className={frameClass} aria-hidden>
            <WindowChrome title="app.agentstore.io/stores/connect" />
            <div className="space-y-3 p-4">
                <div className="flex items-center gap-2 text-sm font-medium">
                    <Store className="size-4 text-indigo-500" />
                    Connect Shopify
                </div>
                <div className="space-y-2">
                    <div className="rounded-lg border border-border/60 bg-background/60 px-3 py-2 text-muted-foreground">
                        demo-store.myshopify.com
                    </div>
                    <div className="rounded-lg border border-border/60 bg-background/60 px-3 py-2 font-mono text-[10px] text-muted-foreground sm:text-xs">
                        shpat_••••••••••••••••
                    </div>
                </div>
                <div className="flex items-center justify-center gap-2 rounded-full bg-indigo-600 py-2 text-xs font-medium text-white">
                    <Link2 className="size-3.5" />
                    Connect store
                </div>
                <div className={panelClass}>
                    <div className="flex items-center gap-2 text-emerald-600 dark:text-emerald-400">
                        <RefreshCw className="size-3.5 animate-spin motion-reduce:animate-none" />
                        Syncing orders, products, customers…
                    </div>
                    <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-muted/60">
                        <div className="h-full w-2/3 rounded-full bg-indigo-500/80" />
                    </div>
                </div>
            </div>
        </div>
    );
}

function ChatMockup() {
    return (
        <div className={frameClass} aria-hidden>
            <WindowChrome title="app.agentstore.io/chat" />
            <div className="flex min-h-[14rem] flex-col p-4">
                <p className="mb-3 text-[10px] text-muted-foreground sm:text-xs">GPT-4o mini · Demo Store</p>
                <div className="ml-auto max-w-[90%] rounded-2xl rounded-tr-md bg-indigo-500/15 px-3 py-2 text-xs leading-relaxed ring-1 ring-indigo-500/20 sm:text-sm">
                    Show unfulfilled orders from last week
                </div>
                <div className="mt-auto space-y-2 pt-6">
                    <div className="rounded-xl border border-border/60 bg-background/70 px-3 py-2.5 text-xs text-muted-foreground">
                        Ask about orders, products, inventory…
                    </div>
                    <div className="flex justify-end">
                        <div className="rounded-full bg-indigo-600 px-4 py-1.5 text-xs font-medium text-white">
                            Send
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

function ReadMockup() {
    return (
        <div className={frameClass} aria-hidden>
            <WindowChrome title="app.agentstore.io/chat" />
            <div className="space-y-2 p-4">
                <div className="ml-auto max-w-[88%] rounded-2xl rounded-tr-md bg-indigo-500/15 px-3 py-2 text-xs ring-1 ring-indigo-500/20">
                    Show unfulfilled orders from last week
                </div>
                <div className="rounded-lg border border-emerald-500/25 bg-emerald-500/5 px-2.5 py-1.5 font-mono text-[10px] text-emerald-700 dark:text-emerald-300">
                    ✓ list_orders · 12 results
                </div>
                <div className={panelClass}>
                    <p className="font-medium">3 unfulfilled orders</p>
                    <div className="mt-2 space-y-1.5 text-muted-foreground">
                        <div className="flex justify-between">
                            <span>#1042 · Sarah M.</span>
                            <span>$89.00</span>
                        </div>
                        <div className="flex justify-between">
                            <span>#1038 · James K.</span>
                            <span>$124.50</span>
                        </div>
                        <div className="flex justify-between">
                            <span>#1031 · Alex R.</span>
                            <span>$56.00</span>
                        </div>
                    </div>
                </div>
                <p className="text-xs leading-relaxed text-muted-foreground">
                    Found 3 unfulfilled orders in your mirror from the last 7 days.
                </p>
            </div>
        </div>
    );
}

function TraceMockup() {
    const traces = [
        '✓ list_orders · 12 results',
        '✓ get_order #1042',
        '✓ get_order #1038',
        '✓ get_metrics · revenue 7d',
    ] as const;

    return (
        <div className={frameClass} aria-hidden>
            <WindowChrome title="app.agentstore.io/chat · action trace" />
            <div className="space-y-2 p-4">
                <div className="ml-auto max-w-[88%] rounded-2xl rounded-tr-md bg-indigo-500/15 px-3 py-2 text-xs ring-1 ring-indigo-500/20">
                    Show unfulfilled orders from last week
                </div>
                <div className="space-y-1.5">
                    {traces.map((line, index) => (
                        <div
                            key={line}
                            className={cn(
                                'rounded-lg border border-border/50 bg-background/60 px-2.5 py-1.5 font-mono text-[10px] text-muted-foreground',
                                'motion-safe:transition-[opacity,transform] motion-safe:duration-300',
                            )}
                            style={{
                                transitionDelay: `${index * 80}ms`,
                                transitionTimingFunction: 'var(--ease-out-strong)',
                            }}
                        >
                            {line}
                        </div>
                    ))}
                </div>
                <div className="flex items-center gap-2 text-xs text-muted-foreground">
                    <Loader2 className="size-3.5 animate-spin motion-reduce:animate-none" />
                    Agent composing answer…
                </div>
            </div>
        </div>
    );
}

function WriteMockup() {
    return (
        <div className={frameClass} aria-hidden>
            <WindowChrome title="app.agentstore.io/chat · confirm write" />
            <div className="space-y-3 p-4">
                <div className="ml-auto max-w-[88%] rounded-2xl rounded-tr-md bg-indigo-500/15 px-3 py-2 text-xs ring-1 ring-indigo-500/20">
                    Fulfill order #1042
                </div>
                <div className="rounded-xl border border-amber-500/30 bg-amber-500/5 p-3 ring-1 ring-amber-500/15">
                    <div className="flex items-start gap-2">
                        <ShieldCheck className="mt-0.5 size-4 shrink-0 text-amber-600 dark:text-amber-400" />
                        <div>
                            <p className="text-sm font-medium">Confirm store write</p>
                            <p className="mt-1 text-xs text-muted-foreground">
                                The agent will call{' '}
                                <code className="rounded bg-muted/60 px-1 font-mono text-[10px]">
                                    fulfill_order
                                </code>{' '}
                                on your live Shopify store.
                            </p>
                        </div>
                    </div>
                    <div className="mt-3 flex gap-2">
                        <div className="flex-1 rounded-lg border border-border/60 py-2 text-center text-xs text-muted-foreground">
                            Cancel
                        </div>
                        <div className="flex flex-1 items-center justify-center gap-1 rounded-lg bg-indigo-600 py-2 text-xs font-medium text-white">
                            <Check className="size-3.5" />
                            Confirm
                        </div>
                    </div>
                </div>
                <p className="text-[10px] text-muted-foreground sm:text-xs">
                    Every write is logged with a full audit trail.
                </p>
            </div>
        </div>
    );
}

export default function AgentStepMockup({ kind, className }: Props) {
    return (
        <div className={cn('w-full', className)}>
            {kind === 'setup' && <SetupMockup />}
            {kind === 'chat' && <ChatMockup />}
            {kind === 'read' && <ReadMockup />}
            {kind === 'trace' && <TraceMockup />}
            {kind === 'write' && <WriteMockup />}
        </div>
    );
}
