import { LayoutGrid, MessageSquare, Sparkles, Store } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
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

type FeatureLayout = 'featured' | 'wide' | 'stack';

const FEATURES: Array<{
    id: string;
    title: string;
    body: string;
    icon: LucideIcon;
    Mockup: FeatureMockup;
    bento: string;
    layout: FeatureLayout;
}> = [
    {
        id: 'chat',
        title: 'AI chat for operations',
        body: 'Ask "fulfill order #1042" or "update stock for SKU-12". The agent shows a live action trace and asks before every write.',
        icon: MessageSquare,
        Mockup: ChatFeatureMockup,
        bento: 'sm:col-span-2 lg:col-span-2',
        layout: 'featured',
    },
    {
        id: 'dashboard',
        title: 'Dashboard that drives action',
        body: 'Revenue, orders, unfulfilled queue, low stock — click any KPI to prefill chat and act.',
        icon: LayoutGrid,
        Mockup: DashboardFeatureMockup,
        bento: 'sm:col-span-1 lg:col-span-1',
        layout: 'stack',
    },
    {
        id: 'shopify',
        title: 'Shopify in minutes',
        body: 'Connect with a custom app Admin API token. Full mirror sync and webhooks keep data fresh.',
        icon: Store,
        Mockup: ShopifyFeatureMockup,
        bento: 'sm:col-span-1 lg:col-span-1',
        layout: 'stack',
    },
    {
        id: 'byok',
        title: 'You control AI cost (BYOK)',
        body: 'Bring your own OpenRouter API key. We never bill you for tokens — flat plans cover the platform.',
        icon: Sparkles,
        Mockup: ByokFeatureMockup,
        bento: 'sm:col-span-2 lg:col-span-2',
        layout: 'wide',
    },
];

function mockupClassName(layout: FeatureLayout): string {
    const base = 'w-full shrink-0';

    switch (layout) {
        case 'featured':
            return cn(
                base,
                'mx-auto h-[9rem] max-h-[9rem] max-w-[14rem]',
                'sm:h-[9.5rem] sm:max-h-[9.5rem] sm:max-w-[16rem]',
                'lg:mx-0 lg:h-[11rem] lg:max-h-[11rem] lg:max-w-none',
            );
        case 'wide':
            return cn(
                base,
                'mx-auto h-[9rem] max-h-[9rem] max-w-[14rem]',
                'sm:max-w-[16rem]',
                'md:mx-0 md:h-[9.5rem] md:max-h-[9.5rem] md:max-w-none',
            );
        case 'stack':
            return cn(
                base,
                'mx-auto h-[9rem] max-h-[9rem] max-w-[14rem]',
                'sm:mx-0 sm:h-[9.5rem] sm:max-h-[9.5rem] sm:max-w-none',
            );
    }
}

type BentoFeatureCardProps = Readonly<{
    title: string;
    body: string;
    icon: LucideIcon;
    Mockup: FeatureMockup;
    bento: string;
    layout: FeatureLayout;
    index: number;
    inView: boolean;
}>;

function BentoFeatureCard({
    title,
    body,
    icon: Icon,
    Mockup,
    bento,
    layout,
    index,
    inView,
}: BentoFeatureCardProps) {
    const isFeatured = layout === 'featured';
    const isWide = layout === 'wide';
    const isStack = layout === 'stack';

    return (
        <li
            className={cn(
                bento,
                'group relative flex h-full min-h-[15rem] flex-col overflow-hidden rounded-2xl border border-border/60 bg-card/80 shadow-[0_4px_20px_-2px_rgb(0_0_0/0.06)] sm:min-h-[16rem]',
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
                    'relative flex h-full flex-1 flex-col gap-4 p-4 sm:p-5',
                    isFeatured &&
                        'lg:grid lg:grid-cols-2 lg:items-center lg:gap-6 xl:gap-8',
                    isWide && 'md:grid md:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)] md:items-center md:gap-6 lg:gap-8',
                )}
            >
                <div className={cn('flex min-w-0 items-start gap-3', isStack && 'mb-1')}>
                    <div className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-indigo-500/10 ring-1 ring-indigo-500/15 transition-colors duration-150 ease-out [@media(hover:hover)]:group-hover:bg-indigo-500/15">
                        <Icon className="size-4 text-indigo-600 dark:text-indigo-400" strokeWidth={2} />
                    </div>
                    <div className="min-w-0 flex-1">
                        <h3 className="text-base font-semibold tracking-tight sm:text-lg">{title}</h3>
                        <p className="mt-1.5 text-sm leading-relaxed text-muted-foreground">{body}</p>
                    </div>
                </div>

                <div className={cn('min-w-0', isStack && 'mt-auto')}>
                    <Mockup className={mockupClassName(layout)} />
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

                <ul className="mt-10 grid list-none grid-cols-1 gap-3 pl-0 sm:mt-12 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 lg:grid-rows-2 lg:items-stretch lg:gap-4">
                    {FEATURES.map(({ id, title, body, icon, Mockup, bento, layout }, index) => (
                        <BentoFeatureCard
                            key={id}
                            title={title}
                            body={body}
                            icon={icon}
                            Mockup={Mockup}
                            bento={bento}
                            layout={layout}
                            index={index}
                            inView={inView}
                        />
                    ))}
                </ul>
            </div>
        </section>
    );
}
