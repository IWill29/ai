import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { register } from '@/routes';

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
    return (
        <section id="pricing" className="scroll-mt-20 px-4 py-16 md:py-24">
            <div className="mx-auto max-w-[var(--landing-max-width)]">
                <h2 className="text-center text-3xl font-semibold tracking-tight">
                    Simple, flat pricing
                </h2>
                <p className="mx-auto mt-3 max-w-xl text-center text-muted-foreground">
                    AI usage is BYOK — you pay OpenRouter directly. Plans cover the AgentStore
                    platform.
                </p>

                <ul className="mt-12 grid gap-6 md:grid-cols-3">
                    {plans.map((plan) => {
                        const highlighted = plan.slug === 'pro';

                        return (
                            <li key={plan.slug}>
                                <Card
                                    className={cn(
                                        'flex h-full flex-col rounded-2xl border-border/60',
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
                                            <Link href={register()}>
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
