import { LayoutGrid, MessageSquare, Sparkles, Store, type LucideIcon } from 'lucide-react';
import SectionHeading from '@/components/marketing/section-heading';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useInViewOnce } from '@/hooks/use-in-view-once';
import { cn } from '@/lib/utils';

const FEATURES: Array<{ title: string; body: string; icon: LucideIcon }> = [
    {
        title: 'AI chat for operations',
        body: 'Ask "fulfill order #1042" or "update stock for SKU-12". The agent calls your store API and shows a live action trace.',
        icon: MessageSquare,
    },
    {
        title: 'Dashboard that drives action',
        body: 'Revenue, orders, unfulfilled queue, low stock — click any KPI to prefill chat and act.',
        icon: LayoutGrid,
    },
    {
        title: 'Shopify in minutes',
        body: 'Connect with a custom app Admin API token. Full mirror sync and webhooks keep data fresh.',
        icon: Store,
    },
    {
        title: 'You control AI cost (BYOK)',
        body: 'Bring your own OpenRouter API key. We never bill you for tokens — flat plans cover the platform.',
        icon: Sparkles,
    },
];

function FeatureCard({
    title,
    body,
    icon: Icon,
    index,
    inView,
}: {
    title: string;
    body: string;
    icon: LucideIcon;
    index: number;
    inView: boolean;
}) {
    return (
        <li
            className={cn(
                inView ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3',
                'motion-safe:transition-[opacity,transform] motion-safe:duration-500',
                'motion-reduce:opacity-100 motion-reduce:translate-y-0 motion-reduce:transition-none',
            )}
            style={{
                transitionTimingFunction: 'var(--ease-out-strong)',
                transitionDelay: inView ? `${index * 70}ms` : '0ms',
            }}
        >
            <Card className="group h-full rounded-2xl border-border/60 shadow-[0_4px_20px_-2px_rgb(0_0_0/0.06)] transition-[border-color,box-shadow] duration-200 ease-out hover:border-indigo-500/30 dark:shadow-[0_8px_32px_-8px_rgb(0_0_0/0.45)]">
                <CardHeader className="flex flex-row items-start gap-3 space-y-0">
                    <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-indigo-500/10 ring-1 ring-indigo-500/15 transition-colors duration-150 ease-out group-hover:bg-indigo-500/15">
                        <Icon className="size-5 text-indigo-600 dark:text-indigo-400" strokeWidth={2} />
                    </div>
                    <CardTitle className="pt-1.5 text-base leading-snug">{title}</CardTitle>
                </CardHeader>
                <CardContent>
                    <p className="text-sm leading-relaxed text-muted-foreground">{body}</p>
                </CardContent>
            </Card>
        </li>
    );
}

export default function FeaturesSection() {
    const { ref, inView } = useInViewOnce<HTMLElement>();

    return (
        <section id="features" ref={ref} className="scroll-mt-20 px-4 py-16 md:py-24">
            <div className="mx-auto max-w-[var(--landing-max-width)]">
                <SectionHeading
                    title="Everything you need to run the store"
                    description="Mirror reads, confirmed writes, and a single workspace for metrics and chat."
                />

                <ul className="mt-12 grid list-none gap-6 pl-0 sm:grid-cols-2">
                    {FEATURES.map(({ title, body, icon }, index) => (
                        <FeatureCard
                            key={title}
                            title={title}
                            body={body}
                            icon={icon}
                            index={index}
                            inView={inView}
                        />
                    ))}
                </ul>
            </div>
        </section>
    );
}
