import { Link } from '@inertiajs/react';
import {
    ArrowRight,
    Link2,
    Lock,
    MessageSquare,
    RefreshCw,
    Sparkles,
    Store,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';
import { openrouter } from '@/routes/settings';
import { connect } from '@/routes/stores';

const cardClass =
    'rounded-2xl border-border/60 bg-card shadow-[0_4px_20px_-2px_rgb(0_0_0/0.08)] dark:border-border/80 dark:shadow-[0_8px_32px_-8px_rgb(0_0_0/0.55)]';

const SETUP_STEPS = [
    { step: '1', label: 'Connect', detail: 'Link Shopify', icon: Link2 },
    { step: '2', label: 'Sync', detail: 'Mirror your catalog', icon: RefreshCw },
    { step: '3', label: 'Ask', detail: 'Chat on your data', icon: MessageSquare },
] as const;

function revealClass(delayClass?: string): string {
    return cn(
        'opacity-100 translate-y-0 scale-100',
        'motion-safe:transition-[opacity,transform] motion-safe:duration-200 motion-safe:ease-out',
        'motion-safe:starting:opacity-0 motion-safe:starting:translate-y-2 motion-safe:starting:scale-[0.97]',
        delayClass,
    );
}

function staggerRevealDelay(index: number, delays: readonly string[]): string {
    return delays[index] ?? delays.at(-1) ?? '';
}

function ChatPreview() {
    return (
        <div
            aria-hidden
            className={cn(
                'relative space-y-3 rounded-xl border border-border/60 bg-muted/25 p-4',
                revealClass('motion-safe:delay-100'),
            )}
        >
            <div className="flex justify-end">
                <div className="max-w-[85%] space-y-1.5 rounded-2xl rounded-tr-md bg-indigo-500/15 px-3 py-2.5 ring-1 ring-indigo-500/20">
                    <Skeleton className="h-2.5 w-36 opacity-60" />
                    <Skeleton className="h-2.5 w-28 opacity-40" />
                </div>
            </div>
            <div className="flex justify-start">
                <div className="max-w-[85%] space-y-1.5 rounded-2xl rounded-tl-md bg-background px-3 py-2.5 ring-1 ring-border/60">
                    <Skeleton className="h-2.5 w-44 opacity-50" />
                    <Skeleton className="h-2.5 w-32 opacity-35" />
                    <Skeleton className="h-2.5 w-24 opacity-25" />
                </div>
            </div>
            <div className="absolute inset-0 flex items-center justify-center rounded-xl bg-card/45 backdrop-blur-[1px]">
                <div className="flex flex-col items-center gap-2">
                    <div className="flex size-10 items-center justify-center rounded-full bg-background/95 shadow-sm ring-1 ring-border/60">
                        <Lock className="size-4 text-muted-foreground" strokeWidth={2} />
                    </div>
                    <span className="text-xs font-medium text-muted-foreground">
                        Waiting for store data
                    </span>
                </div>
            </div>
        </div>
    );
}

function StepRail() {
    return (
        <ol className="flex w-full flex-col gap-3 sm:flex-row sm:items-start sm:gap-0">
            {SETUP_STEPS.map(({ step, label, detail, icon: Icon }, index) => (
                <li
                    key={label}
                    className={cn(
                        'relative flex flex-1 flex-col items-center text-center sm:px-2',
                        revealClass(
                            staggerRevealDelay(index, [
                                'motion-safe:delay-150',
                                'motion-safe:delay-200',
                                'motion-safe:delay-250',
                            ]),
                        ),
                    )}
                >
                    {index > 0 ? (
                        <span
                            aria-hidden
                            className="absolute -left-2 top-4 hidden h-px w-4 bg-border sm:block md:w-6"
                        />
                    ) : null}
                    <div className="mb-2 flex size-9 items-center justify-center rounded-full bg-indigo-500/10 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-500/20 dark:text-indigo-300">
                        {step}
                    </div>
                    <div className="mb-1 flex items-center gap-1.5">
                        <Icon className="size-3.5 text-muted-foreground" strokeWidth={2} aria-hidden />
                        <span className="text-sm font-medium">{label}</span>
                    </div>
                    <span className="text-xs text-muted-foreground">{detail}</span>
                </li>
            ))}
        </ol>
    );
}

export default function ChatEmptyState() {
    return (
        <div className="relative mx-auto flex min-h-[50vh] w-full max-w-3xl items-center justify-center px-4 py-8 md:py-10">
            <Card
                className={cn(
                    cardClass,
                    'relative z-10 w-full max-w-2xl overflow-hidden border-dashed border-border/70',
                    revealClass('motion-safe:delay-75'),
                )}
            >
                <div
                    aria-hidden
                    className="pointer-events-none absolute inset-x-0 top-0 h-32 bg-[radial-gradient(ellipse_at_top,rgba(99,102,241,0.12),transparent_70%)] dark:bg-[radial-gradient(ellipse_at_top,rgba(99,102,241,0.18),transparent_72%)]"
                />

                <CardContent className="relative flex flex-col gap-6 px-6 py-8 sm:px-8">
                    <div className="grid gap-6 md:grid-cols-[minmax(0,1fr)_12rem] md:items-start md:gap-8">
                        <div className="flex flex-col gap-4 text-left">
                            <div
                                className={cn(
                                    'inline-flex w-fit items-center gap-2 rounded-full border border-indigo-500/20 bg-indigo-500/8 px-3 py-1',
                                    revealClass('motion-safe:delay-100'),
                                )}
                            >
                                <MessageSquare
                                    className="size-3.5 text-indigo-600 dark:text-indigo-400"
                                    strokeWidth={2}
                                    aria-hidden
                                />
                                <span className="text-[11px] font-semibold uppercase tracking-wider text-indigo-800 dark:text-indigo-200">
                                    AI chat
                                </span>
                            </div>

                            <div className={cn('space-y-2', revealClass('motion-safe:delay-150'))}>
                                <h2 className="text-xl font-semibold tracking-tight sm:text-2xl">
                                    Connect a store to start chatting
                                </h2>
                                <p className="max-w-md text-sm leading-relaxed text-muted-foreground">
                                    Your agent answers from the synced mirror — orders, products, and
                                    customers. Without a store, there&apos;s nothing to talk about yet.
                                </p>
                            </div>

                            <StepRail />
                        </div>

                        <ChatPreview />
                    </div>

                    <div
                        className={cn(
                            'flex flex-col gap-3 border-t border-border/50 pt-6',
                            revealClass('motion-safe:delay-300'),
                        )}
                    >
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <Button
                                asChild
                                variant="brand"
                                className="w-full rounded-full sm:w-auto sm:px-8"
                            >
                                <Link href={connect()} className="gap-2">
                                    <Store className="size-4" aria-hidden />
                                    Connect Shopify store
                                    <ArrowRight className="size-4" aria-hidden />
                                </Link>
                            </Button>
                            <div className="inline-flex items-center justify-center gap-2 self-center rounded-full border border-emerald-500/25 bg-emerald-500/8 px-3 py-1.5 sm:self-auto">
                                <Sparkles
                                    className="size-3.5 text-emerald-600 dark:text-emerald-400"
                                    strokeWidth={2}
                                    aria-hidden
                                />
                                <span className="text-xs font-medium text-emerald-800 dark:text-emerald-300">
                                    Then add AI keys
                                </span>
                            </div>
                        </div>
                        <p className="text-center text-xs text-muted-foreground sm:text-left">
                            After connecting, sync your catalog and add an{' '}
                            <Link
                                href={openrouter()}
                                className="font-medium text-foreground underline decoration-border underline-offset-4 transition-colors duration-150 ease-out hover:decoration-foreground"
                            >
                                OpenRouter key
                            </Link>{' '}
                            to send your first message.
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
