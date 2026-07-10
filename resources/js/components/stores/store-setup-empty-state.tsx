import { Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Link2,
    Lock,
    MessageSquare,
    Package,
    RefreshCw,
    ShoppingBag,
    ShoppingCart,
    TrendingUp,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';
import { connect } from '@/routes/stores';

const setupCardClass =
    'rounded-2xl border-border/60 bg-card shadow-[0_4px_20px_-2px_rgb(0_0_0/0.08)] dark:border-border/80 dark:shadow-[0_8px_32px_-8px_rgb(0_0_0/0.55)]';

const GHOST_KPIS = [
    { title: 'Revenue', icon: TrendingUp },
    { title: 'Orders', icon: ShoppingCart },
    { title: 'Products', icon: Package },
] as const;

const BENTO_TILES = [
    {
        label: 'Revenue',
        hint: '7-day trend',
        icon: TrendingUp,
        tileClass: 'bg-indigo-500/[0.07]',
        iconClass: 'text-indigo-600 dark:text-indigo-400',
    },
    {
        label: 'Orders',
        hint: 'Live feed',
        icon: ShoppingCart,
        tileClass: 'bg-violet-500/[0.07]',
        iconClass: 'text-violet-600 dark:text-violet-400',
    },
    {
        label: 'Products',
        hint: 'Top sellers',
        icon: Package,
        tileClass: 'bg-emerald-500/[0.07]',
        iconClass: 'text-emerald-600 dark:text-emerald-400',
    },
] as const;

const SETUP_STEPS = [
    { step: '1', label: 'Connect', detail: 'Shopify Admin API', icon: Link2 },
    { step: '2', label: 'Sync', detail: 'Orders & catalog', icon: RefreshCw },
    { step: '3', label: 'Ask', detail: 'AI on your data', icon: MessageSquare },
] as const;

function revealClass(delayClass?: string): string {
    return cn(
        'opacity-100 translate-y-0 scale-100',
        'motion-safe:transition-[opacity,transform] motion-safe:duration-200 motion-safe:ease-out',
        'motion-safe:starting:opacity-0 motion-safe:starting:translate-y-2 motion-safe:starting:scale-[0.97]',
        delayClass,
    );
}

function BentoPreview() {
    return (
        <div
            aria-hidden
            className={cn(
                'grid grid-cols-3 divide-x divide-border/50 border-b border-border/50',
                revealClass(),
            )}
        >
            {BENTO_TILES.map(({ label, hint, icon: Icon, tileClass, iconClass }, index) => (
                <div
                    key={label}
                    className={cn(
                        'group relative flex flex-col gap-3 p-4 sm:p-5',
                        tileClass,
                        revealClass(
                            index === 0
                                ? 'motion-safe:delay-75'
                                : index === 1
                                  ? 'motion-safe:delay-100'
                                  : 'motion-safe:delay-150',
                        ),
                        'motion-safe:transition-transform motion-safe:duration-200 motion-safe:ease-out',
                        'motion-safe:group-hover:scale-[1.02] motion-reduce:group-hover:scale-100',
                    )}
                >
                    <div className="flex items-center justify-between gap-2">
                        <span className="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
                            {label}
                        </span>
                        <Icon className={cn('size-3.5', iconClass)} strokeWidth={2} />
                    </div>
                    <div className="space-y-1.5">
                        <Skeleton className="h-6 w-16 opacity-60" />
                        <Skeleton className="h-2.5 w-20 opacity-40" />
                    </div>
                    <span className="text-[11px] text-muted-foreground/80">{hint}</span>
                    <div className="absolute inset-0 flex items-center justify-center bg-card/55 backdrop-blur-[1px]">
                        <div className="flex size-8 items-center justify-center rounded-full bg-background/90 shadow-sm ring-1 ring-border/60">
                            <Lock className="size-3.5 text-muted-foreground" strokeWidth={2} />
                        </div>
                    </div>
                </div>
            ))}
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
                            index === 0
                                ? 'motion-safe:delay-200'
                                : index === 1
                                  ? 'motion-safe:delay-250'
                                  : 'motion-safe:delay-300',
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

function DashboardMock() {
    return (
        <div
            aria-hidden
            className={cn(
                'relative hidden overflow-hidden rounded-xl border border-border/60 bg-muted/30 p-3 shadow-inner md:block',
                revealClass('motion-safe:delay-200'),
            )}
        >
            <div className="mb-3 flex items-center justify-between">
                <Skeleton className="h-2.5 w-20" />
                <Skeleton className="h-2.5 w-10 rounded-full" />
            </div>
            <div className="relative mb-3 h-24 overflow-hidden rounded-lg bg-background/80 ring-1 ring-border/40">
                <svg
                    className="absolute inset-0 h-full w-full text-indigo-500/35"
                    viewBox="0 0 200 80"
                    preserveAspectRatio="none"
                    aria-hidden
                >
                    <path
                        d="M0 62 C 30 58, 45 28, 70 34 S 120 72, 200 18"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2"
                        strokeLinecap="round"
                    />
                    <path
                        d="M0 62 C 30 58, 45 28, 70 34 S 120 72, 200 18 V 80 H 0 Z"
                        fill="currentColor"
                        className="opacity-[0.12]"
                    />
                </svg>
                <div className="absolute right-2 top-2 inline-flex items-center gap-1 rounded-full bg-emerald-500/15 px-2 py-0.5 text-[10px] font-medium text-emerald-700 ring-1 ring-emerald-500/25 dark:text-emerald-300">
                    <TrendingUp className="size-2.5" strokeWidth={2.5} />
                    +—%
                </div>
            </div>
            <div className="space-y-2">
                <Skeleton className="h-2 w-full opacity-50" />
                <Skeleton className="h-2 w-[80%] opacity-40" />
                <Skeleton className="h-2 w-[60%] opacity-30" />
            </div>
        </div>
    );
}

function GhostMetrics() {
    return (
        <div
            aria-hidden
            className={cn(
                'pointer-events-none absolute inset-x-0 top-0 grid grid-cols-1 gap-3 px-2 sm:grid-cols-3 sm:px-4',
                revealClass(),
            )}
        >
            {GHOST_KPIS.map(({ title, icon: Icon }) => (
                <Card
                    key={title}
                    className="rounded-2xl border-border/40 bg-card/40 opacity-15 shadow-none"
                >
                    <CardHeader className="flex flex-row items-center justify-between pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">
                            {title}
                        </CardTitle>
                        <Icon className="size-4 text-indigo-500/80" />
                    </CardHeader>
                    <CardContent className="space-y-2">
                        <Skeleton className="h-8 w-24" />
                        <Skeleton className="h-3 w-32" />
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}

export default function StoreSetupEmptyState() {
    const { hasConnectedStores } = usePage().props;

    if (hasConnectedStores) {
        return null;
    }

    return (
        <div className="relative mx-auto flex min-h-[60vh] w-full max-w-3xl items-center justify-center px-4 py-10">
            <GhostMetrics />

            <Card
                className={cn(
                    setupCardClass,
                    'relative z-10 w-full max-w-2xl overflow-hidden border-dashed border-border/70',
                    revealClass('motion-safe:delay-75'),
                )}
            >
                <div
                    aria-hidden
                    className="pointer-events-none absolute inset-x-0 top-0 h-32 bg-[radial-gradient(ellipse_at_top,rgba(99,102,241,0.12),transparent_70%)] dark:bg-[radial-gradient(ellipse_at_top,rgba(99,102,241,0.18),transparent_72%)]"
                />

                <BentoPreview />

                <CardContent className="relative flex flex-col gap-6 px-6 py-8 sm:px-8">
                    <div className="grid gap-6 md:grid-cols-[minmax(0,1fr)_11rem] md:items-start md:gap-8">
                        <div className="flex flex-col gap-4 text-left">
                            <div
                                className={cn(
                                    'inline-flex w-fit items-center gap-2 rounded-full border border-indigo-500/20 bg-indigo-500/8 px-3 py-1',
                                    revealClass('motion-safe:delay-100'),
                                )}
                            >
                                <ShoppingBag
                                    className="size-3.5 text-indigo-600 dark:text-indigo-400"
                                    strokeWidth={2}
                                    aria-hidden
                                />
                                <span className="text-[11px] font-semibold uppercase tracking-wider text-indigo-800 dark:text-indigo-200">
                                    Store setup
                                </span>
                            </div>

                            <div className={cn('space-y-2', revealClass('motion-safe:delay-150'))}>
                                <h2 className="text-xl font-semibold tracking-tight sm:text-2xl">
                                    Connect your first store
                                </h2>
                                <p className="max-w-md text-sm leading-relaxed text-muted-foreground">
                                    Link Shopify once — your dashboard, trends, and AI agent all
                                    read from the same mirror.
                                </p>
                            </div>

                            <StepRail />
                        </div>

                        <DashboardMock />
                    </div>

                    <div
                        className={cn(
                            'flex flex-col gap-2 border-t border-border/50 pt-6 sm:flex-row sm:items-center sm:justify-between',
                            revealClass('motion-safe:delay-300'),
                        )}
                    >
                        <div className="flex flex-col gap-1 sm:gap-0.5">
                            <Button
                                asChild
                                variant="brand"
                                className="w-full rounded-full sm:w-auto sm:px-8"
                            >
                                <Link href={connect()} className="gap-2">
                                    Connect Shopify store
                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                            <p className="text-center text-xs text-muted-foreground sm:text-left">
                                ~2 min setup · Admin API keys only
                            </p>
                        </div>

                        <div className="inline-flex items-center justify-center gap-2 self-center rounded-full border border-emerald-500/25 bg-emerald-500/8 px-3 py-1.5 sm:self-auto">
                            <ShoppingBag
                                className="size-3.5 text-emerald-600 dark:text-emerald-400"
                                strokeWidth={2}
                                aria-hidden
                            />
                            <span className="text-xs font-medium text-emerald-800 dark:text-emerald-300">
                                Works with Shopify
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
