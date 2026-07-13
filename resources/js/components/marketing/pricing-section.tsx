import { Check, KeyRound, Sparkles } from 'lucide-react';
import SectionHeading from '@/components/marketing/section-heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useInViewOnce } from '@/hooks/use-in-view-once';
import { MARKETING_ROUTES } from '@/lib/marketing-routes';
import { cn } from '@/lib/utils';

export type PlanRow = {
    slug: string;
    name: string;
    price_cents: number;
    currency: string;
    store_limit: number | null;
    monthly_message_limit: number | null;
};

type PlanMeta = {
    description: string;
    features: string[];
};

const PLAN_META: Record<string, PlanMeta> = {
    free: {
        description: 'Try AgentStore on a single store.',
        features: [
            'Shopify mirror sync & webhooks',
            'Dashboard KPIs with chat handoff',
            'AI agent with confirm-before-write',
            'Bring your own OpenRouter key',
        ],
    },
    pro: {
        description: 'For merchants running multiple stores.',
        features: [
            'Everything in Free',
            'Full read & write tool suite',
            'Nightly reconcile & webhook sync',
        ],
    },
    business: {
        description: 'Unlimited scale for high-volume ops.',
        features: [
            'Everything in Pro',
            'Priority sync & reconcile',
            'Built for teams and agencies',
        ],
    },
};

function formatPrice(priceCents: number, currency: string): string {
    if (priceCents === 0) {
        return 'Free';
    }

    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(priceCents / 100);
}

function formatStoreLimit(limit: number | null): string {
    if (limit === null) {
        return 'Unlimited stores';
    }

    return limit === 1 ? '1 store' : `${limit} stores`;
}

function formatMessageLimit(limit: number | null): string {
    if (limit === null) {
        return 'Unlimited agent messages';
    }

    return `${limit.toLocaleString()} agent messages / mo`;
}

type Props = Readonly<{
    plans: PlanRow[];
}>;

type PricingCardProps = Readonly<{
    plan: PlanRow;
    highlighted: boolean;
    index: number;
    inView: boolean;
}>;

function PricingCard({ plan, highlighted, index, inView }: PricingCardProps) {
    const meta = PLAN_META[plan.slug] ?? {
        description: '',
        features: [formatStoreLimit(plan.store_limit), formatMessageLimit(plan.monthly_message_limit)],
    };

    return (
        <li
            className={cn(
                'relative flex',
                highlighted && 'md:-mt-2 md:mb-2',
                inView ? 'opacity-100' : 'opacity-0',
                'motion-safe:transition-opacity motion-safe:duration-500',
                'motion-reduce:opacity-100 motion-reduce:transition-none',
            )}
            style={{
                transitionTimingFunction: 'var(--ease-out-strong)',
                transitionDelay: inView ? `${index * 70}ms` : '0ms',
            }}
        >
            {highlighted && (
                <div
                    aria-hidden
                    className="pointer-events-none absolute -inset-px rounded-[1.125rem] bg-gradient-to-b from-indigo-500/50 via-indigo-500/20 to-transparent opacity-80"
                />
            )}

            <article
                className={cn(
                    'group relative flex h-full w-full flex-col overflow-hidden rounded-2xl border bg-card/80 p-5 sm:p-6',
                    'shadow-[0_4px_20px_-2px_rgb(0_0_0/0.06)] dark:shadow-[0_8px_32px_-8px_rgb(0_0_0/0.45)]',
                    'transition-[border-color,box-shadow] duration-200 ease-out',
                    highlighted
                        ? 'border-indigo-500/35 shadow-[0_8px_32px_-8px_rgb(99_102_241/0.35)]'
                        : 'border-border/60 [@media(hover:hover)]:hover:border-indigo-500/25',
                )}
                style={{ transitionTimingFunction: 'var(--ease-out-strong)' }}
            >
                <div
                    aria-hidden
                    className={cn(
                        'pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(99,102,241,0.1),transparent_60%)]',
                        highlighted ? 'opacity-100' : 'opacity-0 transition-opacity duration-200 ease-out [@media(hover:hover)]:group-hover:opacity-100',
                    )}
                />

                <div className="relative flex flex-col gap-5">
                    <div className="space-y-3">
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <h3 className="text-lg font-semibold tracking-tight">{plan.name}</h3>
                                <p className="mt-1 text-sm leading-relaxed text-muted-foreground">
                                    {meta.description}
                                </p>
                            </div>
                            {highlighted && (
                                <Badge className="shrink-0 rounded-lg bg-indigo-500/15 text-indigo-700 ring-1 ring-indigo-500/25 dark:text-indigo-300">
                                    Most popular
                                </Badge>
                            )}
                        </div>

                        <div className="flex items-baseline gap-1.5">
                            <span className="text-4xl font-semibold tabular-nums tracking-tight">
                                {formatPrice(plan.price_cents, plan.currency)}
                            </span>
                            {plan.price_cents > 0 && (
                                <span className="text-sm text-muted-foreground">/ month</span>
                            )}
                        </div>
                    </div>

                    <ul className="space-y-2.5 border-t border-border/50 pt-5 text-sm">
                        <li className="flex items-start gap-2.5 text-muted-foreground">
                            <Check
                                className="mt-0.5 size-4 shrink-0 text-indigo-600 dark:text-indigo-400"
                                strokeWidth={2.5}
                            />
                            <span>{formatStoreLimit(plan.store_limit)}</span>
                        </li>
                        <li className="flex items-start gap-2.5 text-muted-foreground">
                            <Check
                                className="mt-0.5 size-4 shrink-0 text-indigo-600 dark:text-indigo-400"
                                strokeWidth={2.5}
                            />
                            <span>{formatMessageLimit(plan.monthly_message_limit)}</span>
                        </li>
                        {meta.features.map((feature) => (
                            <li key={feature} className="flex items-start gap-2.5 text-muted-foreground">
                                <Check
                                    className="mt-0.5 size-4 shrink-0 text-indigo-600 dark:text-indigo-400"
                                    strokeWidth={2.5}
                                />
                                <span>{feature}</span>
                            </li>
                        ))}
                    </ul>

                    <Button
                        asChild
                        variant={highlighted ? 'brand' : 'outline'}
                        className={cn(
                            'mt-auto w-full rounded-full',
                            !highlighted &&
                                'border-border/70 bg-background/60 transition-[border-color,background-color,transform] duration-150 ease-out [@media(hover:hover)]:hover:border-indigo-500/30 [@media(hover:hover)]:hover:bg-indigo-500/5',
                            'active:scale-[0.97] motion-reduce:active:scale-100',
                        )}
                    >
                        <a href={MARKETING_ROUTES.register}>
                            {plan.slug === 'free' ? 'Get started free' : 'Get started'}
                        </a>
                    </Button>
                </div>
            </article>
        </li>
    );
}

export default function PricingSection({ plans }: Props) {
    const { ref, inView } = useInViewOnce<HTMLElement>();

    return (
        <section
            id="pricing"
            ref={ref}
            className="relative scroll-mt-20 overflow-hidden px-4 py-14 sm:py-16 md:py-24"
        >
            <div
                aria-hidden
                className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(99,102,241,0.06),transparent_65%)]"
            />

            <div className="relative mx-auto max-w-[var(--landing-max-width)]">
                <SectionHeading
                    title="Simple, flat pricing"
                    description="AI usage is BYOK — you pay OpenRouter directly. Plans cover the AgentStore platform."
                />

                <div
                    className={cn(
                        'mx-auto mt-8 flex max-w-xl items-center justify-center gap-2 rounded-full border border-indigo-500/20 bg-indigo-500/5 px-4 py-2 text-center text-xs text-muted-foreground sm:mt-10 sm:text-sm',
                        inView ? 'opacity-100' : 'opacity-0',
                        'motion-safe:transition-opacity motion-safe:duration-500 motion-safe:delay-100',
                        'motion-reduce:opacity-100 motion-reduce:transition-none',
                    )}
                    style={{ transitionTimingFunction: 'var(--ease-out-strong)' }}
                >
                    <KeyRound className="size-3.5 shrink-0 text-indigo-600 dark:text-indigo-400" />
                    <span>
                        Platform subscription only — token usage billed by{' '}
                        <span className="font-medium text-foreground">OpenRouter</span>, not AgentStore.
                    </span>
                </div>

                <ul className="mt-8 grid list-none gap-4 pl-0 sm:mt-10 sm:gap-5 md:grid-cols-3 md:items-stretch md:gap-6">
                    {plans.map((plan, index) => (
                        <PricingCard
                            key={plan.slug}
                            plan={plan}
                            highlighted={plan.slug === 'pro'}
                            index={index}
                            inView={inView}
                        />
                    ))}
                </ul>

                <p
                    className={cn(
                        'mt-8 flex items-center justify-center gap-1.5 text-center text-xs text-muted-foreground sm:text-sm',
                        inView ? 'opacity-100' : 'opacity-0',
                        'motion-safe:transition-opacity motion-safe:duration-500 motion-safe:delay-300',
                        'motion-reduce:opacity-100 motion-reduce:transition-none',
                    )}
                    style={{ transitionTimingFunction: 'var(--ease-out-strong)' }}
                >
                    <Sparkles className="size-3.5 shrink-0 text-indigo-500/80" />
                    Pro and Business billing starts after you create an account.
                </p>
            </div>
        </section>
    );
}
