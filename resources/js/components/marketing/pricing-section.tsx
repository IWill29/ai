import { Link } from '@inertiajs/react';
import SectionHeading from '@/components/marketing/section-heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { useInViewOnce } from '@/hooks/use-in-view-once';
import { cn } from '@/lib/utils';
import { MARKETING_ROUTES } from '@/lib/marketing-routes';

export type PlanRow = {
    slug: string;
    name: string;
    price_cents: number;
    currency: string;
    store_limit: number | null;
    monthly_message_limit: number | null;
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

    return `${limit} agent messages / mo`;
}

type Props = {
    plans: PlanRow[];
};

export default function PricingSection({ plans }: Props) {
    const { ref, inView } = useInViewOnce<HTMLElement>();

    return (
        <section id="pricing" ref={ref} className="scroll-mt-20 px-4 py-14 sm:py-16 md:py-24">
            <div className="mx-auto max-w-[var(--landing-max-width)]">
                <SectionHeading
                    title="Simple, flat pricing"
                    description="AI usage is BYOK — you pay OpenRouter directly. Plans cover the AgentStore platform."
                />

                <ul className="mt-10 grid list-none gap-4 pl-0 sm:mt-12 sm:gap-6 md:grid-cols-3">
                    {plans.map((plan, index) => {
                        const highlighted = plan.slug === 'pro';

                        return (
                            <li
                                key={plan.slug}
                                className={cn(
                                    inView ? 'opacity-100' : 'opacity-0',
                                    'motion-safe:transition-opacity motion-safe:duration-500',
                                    'motion-reduce:opacity-100 motion-reduce:transition-none',
                                )}
                                style={{
                                    transitionTimingFunction: 'var(--ease-out-strong)',
                                    transitionDelay: inView ? `${index * 80}ms` : '0ms',
                                }}
                            >
                                <Card
                                    className={cn(
                                        'flex h-full flex-col rounded-2xl border-border/60 shadow-[0_4px_20px_-2px_rgb(0_0_0/0.06)] dark:shadow-[0_8px_32px_-8px_rgb(0_0_0/0.45)]',
                                        highlighted &&
                                            'ring-1 ring-indigo-500/40 shadow-[0_4px_24px_-4px_rgb(99_102_241/0.25)]',
                                    )}
                                >
                                    <CardHeader>
                                        <div className="flex items-center justify-between gap-2">
                                            <CardTitle>{plan.name}</CardTitle>
                                            {highlighted && (
                                                <Badge className="rounded-lg">Most popular</Badge>
                                            )}
                                        </div>
                                        <p className="text-3xl font-semibold tracking-tight">
                                            {formatPrice(plan.price_cents, plan.currency)}
                                            {plan.price_cents > 0 && (
                                                <span className="text-sm font-normal text-muted-foreground">
                                                    {' '}
                                                    / mo
                                                </span>
                                            )}
                                        </p>
                                    </CardHeader>
                                    <CardContent className="space-y-2 text-sm text-muted-foreground">
                                        <p>{formatStoreLimit(plan.store_limit)}</p>
                                        <p>{formatMessageLimit(plan.monthly_message_limit)}</p>
                                    </CardContent>
                                    <CardFooter className="mt-auto pt-0">
                                        <Button
                                            asChild
                                            variant={highlighted ? 'brand' : 'outline'}
                                            className="w-full rounded-full active:scale-[0.97] motion-reduce:active:scale-100"
                                        >
                                            <Link href={MARKETING_ROUTES.register}>
                                                {plan.slug === 'free'
                                                    ? 'Get started free'
                                                    : 'Get started'}
                                            </Link>
                                        </Button>
                                    </CardFooter>
                                </Card>
                            </li>
                        );
                    })}
                </ul>

                <p className="mt-8 text-center text-xs text-muted-foreground">
                    Pro and Business billing starts after you create an account.
                </p>
            </div>
        </section>
    );
}
