import {
    ArrowUpRight,
    LayoutGrid,
    MessageSquare,
    Package,
    Plus,
    ShoppingCart,
    Store,
    TrendingUp,
} from 'lucide-react';
import { useEffect, useState, useSyncExternalStore  } from 'react';
import type {CSSProperties} from 'react';
import AppLogo from '@/components/app-logo';
import { useInViewOnce } from '@/hooks/use-in-view-once';
import { cn } from '@/lib/utils';

const dashboardCardClass =
    'rounded-xl border border-border/60 bg-card shadow-[0_4px_20px_-2px_rgb(0_0_0/0.08)] dark:border-border/80 dark:shadow-[0_8px_32px_-8px_rgb(0_0_0/0.55)]';

const DEMO_ORDERS = [
    { id: '#1042', customer: 'Sarah M.', total: '$89.00' },
    { id: '#1038', customer: 'James K.', total: '$124.50' },
] as const;

type DemoPhase =
    | 'kpis'
    | 'highlight'
    | 'chat'
    | 'user-message'
    | 'agent-message'
    | 'tool-trace';

function usePrefersReducedMotion(): boolean {
    return useSyncExternalStore(
        (onStoreChange) => {
            const media = window.matchMedia('(prefers-reduced-motion: reduce)');
            media.addEventListener('change', onStoreChange);

            return () => media.removeEventListener('change', onStoreChange);
        },
        () => window.matchMedia('(prefers-reduced-motion: reduce)').matches,
        () => false,
    );
}

function usePageVisible(): boolean {
    const [visible, setVisible] = useState(
        typeof document === 'undefined' ? true : document.visibilityState === 'visible',
    );

    useEffect(() => {
        const onVisibility = (): void => setVisible(document.visibilityState === 'visible');
        document.addEventListener('visibilitychange', onVisibility);

        return () => document.removeEventListener('visibilitychange', onVisibility);
    }, []);

    return visible;
}

function useDemoPhase(reducedMotion: boolean, enabled: boolean): DemoPhase {
    const [phase, setPhase] = useState<DemoPhase>(reducedMotion ? 'tool-trace' : 'kpis');

    useEffect(() => {
        if (reducedMotion || !enabled) {
            return;
        }

        const steps: Array<[DemoPhase, number]> = [
            ['kpis', 1200],
            ['highlight', 800],
            ['chat', 500],
            ['user-message', 600],
            ['agent-message', 700],
            ['tool-trace', 3500],
        ];

        let index = 0;
        let timeoutId = 0;

        const advance = (): void => {
            index = (index + 1) % steps.length;
            setPhase(steps[index][0]);
            timeoutId = window.setTimeout(advance, steps[index][1]);
        };

        timeoutId = window.setTimeout(advance, steps[0][1]);

        return () => window.clearTimeout(timeoutId);
    }, [enabled, reducedMotion]);

    return phase;
}

function useCountUp(target: number, active: boolean, duration = 900): number {
    const [value, setValue] = useState(0);

    useEffect(() => {
        if (!active) {
            return;
        }

        const start = performance.now();
        let frame = 0;

        const tick = (now: number): void => {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - (1 - progress) ** 3;
            setValue(Math.round(target * eased));

            if (progress < 1) {
                frame = requestAnimationFrame(tick);
            }
        };

        frame = requestAnimationFrame(tick);

        return () => cancelAnimationFrame(frame);
    }, [active, duration, target]);

    return active ? value : target;
}

function revealClass(visible: boolean): string {
    return cn(
        visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2',
        'motion-safe:transition-[opacity,transform] motion-safe:duration-500',
        'motion-reduce:opacity-100 motion-reduce:translate-y-0 motion-reduce:transition-none',
    );
}

function revealStyle(visible: boolean, delayMs = 0): CSSProperties {
    return {
        transitionTimingFunction: 'var(--ease-out-strong)',
        transitionDelay: visible ? `${delayMs}ms` : '0ms',
    };
}

function MockSidebar() {
    const navItemClass = (active: boolean): string =>
        cn(
            'flex items-center gap-2 rounded-lg px-2.5 py-2 text-xs font-medium transition-colors duration-150 ease-out',
            active
                ? 'bg-indigo-500/10 text-indigo-700 ring-1 ring-indigo-500/15 dark:text-indigo-300'
                : 'text-muted-foreground',
        );

    return (
        <aside className="hidden w-40 shrink-0 flex-col border-r border-border/60 bg-sidebar/40 p-3 md:flex lg:w-48 lg:p-4">
            <div className="mb-4">
                <AppLogo compact />
            </div>

            <p className="mb-1.5 px-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-muted-foreground/75">
                Workspace
            </p>
            <nav className="space-y-1" aria-hidden>
                <div className={navItemClass(true)}>
                    <LayoutGrid className="size-4 shrink-0 text-indigo-600 dark:text-indigo-400" />
                    Dashboard
                </div>
                <div className={navItemClass(false)}>
                    <MessageSquare className="size-4 shrink-0" />
                    Chat
                </div>
            </nav>

            <p className="mb-1.5 mt-4 px-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-muted-foreground/75">
                Commerce
            </p>
            <div className={navItemClass(false)}>
                <Store className="size-4 shrink-0" />
                Stores
            </div>

            <div className="mt-auto pt-4">
                <div className="flex items-center justify-center gap-1.5 rounded-full bg-indigo-600 px-3 py-2 text-xs font-medium text-white shadow-sm">
                    <Plus className="size-3.5" />
                    Connect
                </div>
            </div>
        </aside>
    );
}

function MockKpiCard({
    title,
    value,
    subtitle,
    icon: Icon,
    visible,
    delayMs,
    highlighted = false,
    footer,
}: {
    title: string;
    value: string;
    subtitle?: string;
    icon: typeof TrendingUp;
    visible: boolean;
    delayMs: number;
    highlighted?: boolean;
    footer?: string;
}) {
    return (
        <div
            className={cn(
                dashboardCardClass,
                'p-3 transition-[box-shadow,ring-color] duration-300 ease-out sm:p-4',
                highlighted && 'ring-2 ring-indigo-500/40 shadow-[0_8px_24px_-4px_rgb(99_102_241/0.2)]',
                revealClass(visible),
            )}
            style={revealStyle(visible, delayMs)}
        >
            <div className="flex items-center justify-between gap-2">
                <span className="text-[11px] font-medium text-muted-foreground sm:text-xs">{title}</span>
                <Icon className="size-3.5 text-indigo-500 sm:size-4" />
            </div>
            <p className="mt-1.5 text-lg font-semibold tracking-tight sm:mt-2 sm:text-2xl">{value}</p>
            {subtitle && <p className="mt-0.5 text-[10px] text-muted-foreground sm:mt-1 sm:text-xs">{subtitle}</p>}
            {footer && (
                <span className="mt-1.5 inline-flex items-center gap-1 text-[10px] text-indigo-600 dark:text-indigo-400 sm:mt-2 sm:text-[11px]">
                    {footer}
                    <ArrowUpRight className="size-3" />
                </span>
            )}
        </div>
    );
}

function MockDashboard({
    phase,
    revenueDisplay,
    ordersCount,
}: {
    phase: DemoPhase;
    revenueDisplay: string;
    ordersCount: number;
}) {
    const highlightUnfulfilled = ['highlight', 'chat', 'user-message', 'agent-message', 'tool-trace'].includes(
        phase,
    );

    return (
        <div className="flex min-w-0 flex-1 flex-col">
            <div className="flex flex-wrap items-center justify-between gap-2 border-b border-border/50 px-3 py-2.5 sm:gap-3 sm:px-5 sm:py-3">
                <div className="min-w-0">
                    <p className="text-sm font-semibold tracking-tight">Dashboard</p>
                    <p className="truncate text-xs text-muted-foreground">Demo Store · Last 7 days</p>
                </div>
                <div className="hidden items-center gap-2 sm:flex">
                    <div className="rounded-lg border border-border/60 bg-muted/30 px-2.5 py-1 text-xs text-muted-foreground">
                        Demo Store
                    </div>
                    <div className="rounded-lg border border-border/60 bg-muted/30 px-2.5 py-1 text-xs text-muted-foreground">
                        7 days
                    </div>
                </div>
            </div>

            <div className="space-y-3 p-3 sm:space-y-4 sm:p-5">
                <div className="grid grid-cols-2 gap-2 sm:gap-3 lg:grid-cols-4 lg:gap-4">
                    <MockKpiCard
                        title="Revenue"
                        value={revenueDisplay}
                        icon={TrendingUp}
                        visible
                        delayMs={0}
                        subtitle="+14% vs previous"
                    />
                    <MockKpiCard
                        title="Orders"
                        value={String(ordersCount)}
                        icon={ShoppingCart}
                        visible
                        delayMs={80}
                        subtitle="AOV $79.50"
                    />
                    <MockKpiCard
                        title="Unfulfilled"
                        value="3"
                        icon={ShoppingCart}
                        visible
                        delayMs={160}
                        highlighted={highlightUnfulfilled}
                        footer="Ask agent"
                    />
                    <MockKpiCard
                        title="Stock alerts"
                        value="2 low"
                        icon={Package}
                        visible
                        delayMs={240}
                        footer="Ask agent"
                    />
                </div>

                <div
                    className={cn(dashboardCardClass, 'hidden p-4 sm:block sm:p-5', revealClass(true))}
                    style={revealStyle(true, 320)}
                >
                    <p className="text-sm font-medium">Recent orders</p>
                    <table className="mt-3 w-full text-sm">
                        <tbody>
                            {DEMO_ORDERS.map((order, index) => (
                                <tr
                                    key={order.id}
                                    className={cn('border-b border-border/40 last:border-0', revealClass(true))}
                                    style={revealStyle(true, 380 + index * 60)}
                                >
                                    <td className="py-2 font-medium">{order.id}</td>
                                    <td className="py-2 text-muted-foreground">{order.customer}</td>
                                    <td className="py-2 text-right">{order.total}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
}

function MockChatPanel({ phase }: { phase: DemoPhase }) {
    const panelOpen = ['chat', 'user-message', 'agent-message', 'tool-trace'].includes(phase);
    const userVisible = ['user-message', 'agent-message', 'tool-trace'].includes(phase);
    const agentVisible = ['agent-message', 'tool-trace'].includes(phase);
    const toolVisible = phase === 'tool-trace';

    return (
        <aside
            className={cn(
                'hidden shrink-0 flex-col overflow-hidden border-l border-border/60 bg-muted/15 lg:flex',
                panelOpen ? 'w-56 xl:w-64' : 'w-0 border-l-0',
                'motion-safe:transition-[width] motion-safe:duration-500 motion-reduce:transition-none',
            )}
            style={{ transitionTimingFunction: 'var(--ease-out-strong)' }}
        >
            <div className="min-w-56 border-b border-border/50 px-4 py-3 xl:min-w-64">
                <p className="text-sm font-semibold">Chat</p>
                <p className="text-xs text-muted-foreground">GPT-4o mini</p>
            </div>

            <div className="flex min-w-56 flex-1 flex-col gap-3 p-4 xl:min-w-64">
                <div
                    className={cn(
                        'ml-auto max-w-[92%] rounded-2xl rounded-tr-md bg-indigo-500/15 px-3 py-2 text-xs leading-relaxed ring-1 ring-indigo-500/20 sm:text-sm',
                        userVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-1',
                        'motion-safe:transition-[opacity,transform] motion-safe:duration-350',
                        'motion-reduce:opacity-100 motion-reduce:translate-y-0',
                    )}
                    style={{ transitionTimingFunction: 'var(--ease-out-strong)' }}
                >
                    Show unfulfilled orders from last week
                </div>

                <div
                    className={cn(
                        dashboardCardClass,
                        'space-y-2 p-3 text-xs sm:text-sm',
                        agentVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-1',
                        'motion-safe:transition-[opacity,transform] motion-safe:duration-350 motion-safe:delay-100',
                        'motion-reduce:opacity-100 motion-reduce:translate-y-0 motion-reduce:delay-0',
                    )}
                    style={{ transitionTimingFunction: 'var(--ease-out-strong)' }}
                >
                    <p className="font-medium">3 unfulfilled orders</p>
                    {DEMO_ORDERS.map((order) => (
                        <div
                            key={order.id}
                            className="flex justify-between gap-2 text-muted-foreground"
                        >
                            <span>{order.id}</span>
                            <span>{order.total}</span>
                        </div>
                    ))}
                </div>

                <div
                    className={cn(
                        'rounded-xl border border-border/50 bg-background/60 px-3 py-2 text-xs text-muted-foreground',
                        toolVisible ? 'opacity-100' : 'opacity-0',
                        'motion-safe:transition-opacity motion-safe:duration-300 motion-safe:delay-150',
                        'motion-reduce:opacity-100',
                    )}
                >
                    ✓ Searched orders · 12 results
                </div>

                <div className="mt-auto h-10 rounded-xl border border-border/60 bg-background/70" />
            </div>
        </aside>
    );
}

function MockMobileChatStrip({ phase }: { phase: DemoPhase }) {
    const userVisible = ['user-message', 'agent-message', 'tool-trace'].includes(phase);
    const agentVisible = ['agent-message', 'tool-trace'].includes(phase);

    return (
        <div
            className="border-t border-border/60 bg-muted/10 px-3 py-3 lg:hidden"
            aria-hidden
        >
            <div className="mb-2 flex items-center justify-between gap-2">
                <p className="text-xs font-semibold">Chat</p>
                <span className="text-[10px] text-muted-foreground">GPT-4o mini</span>
            </div>
            <div className="space-y-2">
                <div
                    className={cn(
                        'ml-auto max-w-[90%] rounded-xl rounded-tr-sm bg-indigo-500/15 px-2.5 py-1.5 text-[11px] ring-1 ring-indigo-500/20',
                        userVisible ? 'opacity-100' : 'opacity-0',
                        'motion-safe:transition-opacity motion-safe:duration-300 motion-reduce:opacity-100',
                    )}
                >
                    Show unfulfilled orders
                </div>
                <div
                    className={cn(
                        'rounded-lg border border-border/50 bg-card/80 px-2.5 py-2 text-[11px]',
                        agentVisible ? 'opacity-100' : 'opacity-0',
                        'motion-safe:transition-opacity motion-safe:duration-300 motion-reduce:opacity-100',
                    )}
                >
                    3 unfulfilled orders found
                </div>
            </div>
        </div>
    );
}

export default function ChatMockup() {
    const { ref, inView } = useInViewOnce<HTMLDivElement>(0.15, '100px');
    const reducedMotion = usePrefersReducedMotion();
    const pageVisible = usePageVisible();
    const animationEnabled = inView && pageVisible && !reducedMotion;
    const phase = useDemoPhase(reducedMotion, animationEnabled);
    const revenueRaw = useCountUp(12480, animationEnabled, 900);
    const ordersCount = useCountUp(156, animationEnabled, 850);
    const revenueDisplay = `$${(animationEnabled ? revenueRaw : 12480).toLocaleString()}`;

    return (
        <div
            ref={ref}
            aria-hidden
            className={cn(
                'h-full w-full overflow-hidden rounded-2xl border border-border/60 bg-card/95 shadow-[0_24px_80px_-12px_rgb(0_0_0/0.35)] sm:rounded-3xl',
            )}
        >
            <div className="flex items-center gap-2 border-b border-border/50 bg-muted/20 px-3 py-2 sm:px-5 sm:py-2.5">
                <span className="size-2 rounded-full bg-red-400/70 sm:size-2.5" />
                <span className="size-2 rounded-full bg-amber-400/70 sm:size-2.5" />
                <span className="size-2 rounded-full bg-emerald-400/70 sm:size-2.5" />
                <span className="ml-1 truncate text-[10px] text-muted-foreground sm:text-xs">
                    app.agentstore.io/dashboard
                </span>
            </div>

            <div className="flex min-h-[22rem] flex-col lg:min-h-[26rem]">
                <div className="flex min-h-0 flex-1">
                    <MockSidebar />
                    <MockDashboard phase={phase} revenueDisplay={revenueDisplay} ordersCount={ordersCount} />
                    <MockChatPanel phase={phase} />
                </div>
                <MockMobileChatStrip phase={phase} />
            </div>
        </div>
    );
}
