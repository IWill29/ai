import { ArrowUpRight } from 'lucide-react';
import type { ComponentType } from 'react';
import { OpenRouterLogo, ShopifyLogo, StripeLogo } from '@/components/marketing/partner-logos';
import SectionHeading from '@/components/marketing/section-heading';
import { useInViewOnce } from '@/hooks/use-in-view-once';
import { cn } from '@/lib/utils';

type Partner = {
    id: string;
    name: string;
    role: string;
    href: string;
    Logo: ComponentType<{ className?: string }>;
    logoClassName: string;
    iconWrapClassName: string;
};

const PARTNERS: Partner[] = [
    {
        id: 'shopify',
        name: 'Shopify',
        role: 'Store data, Admin API, and webhooks',
        href: 'https://www.shopify.com',
        Logo: ShopifyLogo,
        logoClassName: 'text-[#95BF47]',
        iconWrapClassName: 'bg-[#95BF47]/10 ring-[#95BF47]/20',
    },
    {
        id: 'openrouter',
        name: 'OpenRouter',
        role: 'BYOK access to frontier AI models',
        href: 'https://openrouter.ai',
        Logo: OpenRouterLogo,
        logoClassName: 'text-foreground/85',
        iconWrapClassName: 'bg-indigo-500/10 ring-indigo-500/20',
    },
    {
        id: 'stripe',
        name: 'Stripe',
        role: 'Flat subscriptions and secure billing',
        href: 'https://stripe.com',
        Logo: StripeLogo,
        logoClassName: 'text-[#635BFF]',
        iconWrapClassName: 'bg-[#635BFF]/10 ring-[#635BFF]/20',
    },
];

type PartnerCardProps = Readonly<{
    partner: Partner;
    index: number;
    inView: boolean;
}>;

function PartnerCard({ partner, index, inView }: PartnerCardProps) {
    const { Logo, name, role, href, logoClassName, iconWrapClassName } = partner;

    return (
        <li
            className={cn(
                inView ? 'opacity-100' : 'opacity-0',
                'motion-safe:transition-opacity motion-safe:duration-500',
                'motion-reduce:opacity-100 motion-reduce:transition-none',
            )}
            style={{
                transitionTimingFunction: 'var(--ease-out-strong)',
                transitionDelay: inView ? `${index * 70}ms` : '0ms',
            }}
        >
            <a
                href={href}
                target="_blank"
                rel="noopener noreferrer"
                className={cn(
                    'group relative flex h-full flex-col gap-4 overflow-hidden rounded-2xl border border-border/60 bg-card/70 p-5 sm:p-6',
                    'shadow-[0_4px_20px_-2px_rgb(0_0_0/0.05)] dark:shadow-[0_8px_32px_-8px_rgb(0_0_0/0.4)]',
                    'transition-[border-color,box-shadow,background-color] duration-200 ease-out',
                    '[@media(hover:hover)]:hover:border-indigo-500/30 [@media(hover:hover)]:hover:bg-card/90',
                    '[@media(hover:hover)]:hover:shadow-[0_8px_28px_-8px_rgb(99_102_241/0.2)]',
                    'active:scale-[0.995] motion-reduce:active:scale-100',
                )}
                style={{ transitionTimingFunction: 'var(--ease-out-strong)' }}
            >
                <div
                    aria-hidden
                    className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(99,102,241,0.07),transparent_60%)] opacity-0 transition-opacity duration-200 ease-out [@media(hover:hover)]:group-hover:opacity-100 motion-reduce:transition-none"
                />

                <div className="relative flex items-start justify-between gap-3">
                    <div
                        className={cn(
                            'flex size-12 shrink-0 items-center justify-center rounded-xl ring-1',
                            iconWrapClassName,
                        )}
                    >
                        <Logo className={cn('size-7', logoClassName)} />
                    </div>
                    <ArrowUpRight
                        aria-hidden
                        className="size-4 shrink-0 text-muted-foreground/50 transition-[color,transform] duration-150 ease-out [@media(hover:hover)]:group-hover:-translate-y-0.5 [@media(hover:hover)]:group-hover:translate-x-0.5 [@media(hover:hover)]:group-hover:text-indigo-600 dark:[@media(hover:hover)]:group-hover:text-indigo-400"
                    />
                </div>

                <div className="relative space-y-1.5">
                    <p className="text-base font-semibold tracking-tight">{name}</p>
                    <p className="text-sm leading-relaxed text-muted-foreground">{role}</p>
                </div>
            </a>
        </li>
    );
}

export default function PoweredBySection() {
    const { ref, inView } = useInViewOnce<HTMLElement>();

    return (
        <section
            id="powered-by"
            ref={ref}
            className="relative scroll-mt-20 overflow-hidden border-y border-border/40 px-4 py-14 sm:py-16 md:py-20"
        >
            <div
                aria-hidden
                className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(99,102,241,0.04),transparent_70%)]"
            />

            <div className="relative mx-auto max-w-[var(--landing-max-width)]">
                <SectionHeading
                    title="Powered by the stack merchants trust"
                    description="AgentStore connects Shopify for commerce data, OpenRouter for AI, and Stripe for platform billing."
                />

                <ul className="mt-10 grid list-none gap-4 pl-0 sm:mt-12 sm:grid-cols-3 sm:gap-5">
                    {PARTNERS.map((partner, index) => (
                        <PartnerCard key={partner.id} partner={partner} index={index} inView={inView} />
                    ))}
                </ul>

                <p className="mt-8 text-center text-xs leading-relaxed text-muted-foreground sm:text-sm">
                    Shopify, OpenRouter, and Stripe are trademarks of their respective owners. AgentStore is
                    not affiliated with or endorsed by these companies.
                </p>
            </div>
        </section>
    );
}
