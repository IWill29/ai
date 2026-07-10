import { LayoutGrid, MessageSquare, Sparkles, Store  } from 'lucide-react';
import type {LucideIcon} from 'lucide-react';
import type { ComponentType } from 'react';
import {
    ByokFeatureMockup,
    ChatFeatureMockup,
    DashboardFeatureMockup,
    ShopifyFeatureMockup,
} from '@/components/marketing/feature-mockups';
import SectionHeading from '@/components/marketing/section-heading';
import { useInViewOnce } from '@/hooks/use-in-view-once';
import { cn } from '@/lib/utils';

type FeatureMockup = ComponentType<{ className?: string }>;

const FEATURES: Array<{
    id: string;
    title: string;
    body: string;
    icon: LucideIcon;
    Mockup: FeatureMockup;
    bento: string;
}> = [
    {
        id: 'chat',
        title: 'AI chat for operations',
        body: 'Ask "fulfill order #1042" or "update stock for SKU-12". The agent shows a live action trace and asks before every write.',
        icon: MessageSquare,
        Mockup: ChatFeatureMockup,
        bento: 'sm:col-span-2 lg:col-span-2 lg:row-span-2',
    },
    {
        id: 'dashboard',
        title: 'Dashboard that drives action',
        body: 'Revenue, orders, unfulfilled queue, low stock — click any KPI to prefill chat and act.',
        icon: LayoutGrid,
        Mockup: DashboardFeatureMockup,
        bento: 'sm:col-span-1 lg:col-span-1',
    },
    {
        id: 'shopify',
        title: 'Shopify in minutes',
        body: 'Connect with a custom app Admin API token. Full mirror sync and webhooks keep data fresh.',
        icon: Store,
        Mockup: ShopifyFeatureMockup,
        bento: 'sm:col-span-1 lg:col-span-1',
    },
    {
        id: 'byok',
        title: 'You control AI cost (BYOK)',
        body: 'Bring your own OpenRouter API key. We never bill you for tokens — flat plans cover the platform.',
        icon: Sparkles,
        Mockup: ByokFeatureMockup,
        bento: 'sm:col-span-2 lg:col-span-3',
    },
];

function BentoFeatureCard({
    title,
    body,
    icon: Icon,
    Mockup,
    bento,
    index,
    inView,
    featured = false,
    wide = false,
}: {
    title: string;
    body: string;
    icon: LucideIcon;
    Mockup: FeatureMockup;
    bento: string;
    index: number;
    inView: boolean;
    featured?: boolean;
    wide?: boolean;
}) {
    return (
        <li
            className={cn(
                bento,
                'group relative flex min-h-[14rem] flex-col overflow-hidden rounded-2xl border border-border/60 bg-card/80 shadow-[0_4px_20px_-2px_rgb(0_0_0/0.06)] sm:min-h-[16rem]',
                'transition-[border-color,box-shadow,opacity] duration-200 ease-out',
                '[@media(hover:hover)]:hover:border-indigo-500/30',
                'dark:shadow-[0_8px_32px_-8px_rgb(0_0_0/0.45)]',
                inView ? 'opacity-100' : 'opacity-0',
                'motion-safe:transition-opacity motion-safe:duration-500',
                'motion-reduce:opacity-100 motion-reduce:transition-none',
            )}
            style={{
                transitionTimingFunction: 'var(--ease-out-strong)',
                transitionDelay: inView ? `${index * 70}ms` : '0ms',
            }}
        >
            <div
                aria-hidden
                className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(99,102,241,0.08),transparent_55%)] opacity-0 transition-opacity duration-200 ease-out [@media(hover:hover)]:group-hover:opacity-100 motion-reduce:transition-none"
            />

            <div
                className={cn(
                    'relative flex flex-1 flex-col p-4 sm:p-5',
                    featured && 'lg:flex-row lg:items-stretch lg:gap-6',
                    wide && 'md:flex-row md:items-center md:gap-6 lg:gap-8',
                )}
            >
                <div
                    className={cn(
                        'mb-3 flex items-start gap-3',
                        featured && 'lg:mb-0 lg:max-w-[42%] lg:shrink-0 lg:self-center',
                        wide && 'md:mb-0 md:max-w-md md:shrink-0',
                    )}
                >
                    <div className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-indigo-500/10 ring-1 ring-indigo-500/15 transition-colors duration-150 ease-out [@media(hover:hover)]:group-hover:bg-indigo-500/15">
                        <Icon className="size-4 text-indigo-600 dark:text-indigo-400" strokeWidth={2} />
                    </div>
                    <div className="min-w-0 flex-1">
                        <h3 className="text-base font-semibold tracking-tight sm:text-lg">{title}</h3>
                        <p className="mt-1.5 text-sm leading-relaxed text-muted-foreground">{body}</p>
                    </div>
                </div>

                <div
                    className={cn(
                        'mt-auto min-w-0',
                        featured && 'lg:mt-0 lg:flex-1 lg:min-h-[12rem]',
                        wide && 'md:mt-0 md:min-w-0 md:flex-1',
                        !featured && !wide && 'min-h-[9rem]',
                    )}
                >
                    <Mockup className="h-full min-h-[9rem] w-full sm:min-h-[9.5rem]" />
                </div>
            </div>
        </li>
    );
}

export default function FeaturesSection() {
    const { ref, inView } = useInViewOnce<HTMLElement>();

    return (
        <section
            id="features"
            ref={ref}
            className="scroll-mt-20 px-4 py-14 sm:py-16 md:py-24"
        >
            <div className="mx-auto max-w-[var(--landing-max-width)]">
                <SectionHeading
                    title="Everything you need to run the store"
                    description="Mirror reads, confirmed writes, and a single workspace for metrics and chat."
                />

                <ul className="mt-10 grid list-none grid-cols-1 gap-3 pl-0 sm:mt-12 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 lg:auto-rows-[minmax(15rem,auto)]">
                    {FEATURES.map(({ id, title, body, icon, Mockup, bento }, index) => (
                        <BentoFeatureCard
                            key={id}
                            title={title}
                            body={body}
                            icon={icon}
                            Mockup={Mockup}
                            bento={bento}
                            index={index}
                            inView={inView}
                            featured={id === 'chat'}
                            wide={id === 'byok'}
                        />
                    ))}
                </ul>
            </div>
        </section>
    );
}
