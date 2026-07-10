import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

const FEATURES = [
    {
        title: 'AI chat for operations',
        body: 'Ask "fulfill order #1042" or "update stock for SKU-12". The agent calls your store API and shows a live action trace.',
    },
    {
        title: 'Dashboard that drives action',
        body: 'Revenue, orders, unfulfilled queue, low stock — click any KPI to prefill chat and act.',
    },
    {
        title: 'Shopify in minutes',
        body: 'Connect with a custom app Admin API token. Full mirror sync and webhooks keep data fresh.',
    },
    {
        title: 'You control AI cost (BYOK)',
        body: 'Bring your own OpenRouter API key. We never bill you for tokens — flat plans cover the platform.',
    },
] as const;

export default function FeaturesSection() {
    return (
        <section id="features" className="scroll-mt-20 px-4 py-16 md:py-24">
            <div className="mx-auto max-w-[var(--landing-max-width)]">
                <h2 className="text-center text-3xl font-semibold tracking-tight">
                    Everything you need to run the store
                </h2>
                <p className="mx-auto mt-3 max-w-2xl text-center text-muted-foreground">
                    Mirror reads, confirmed writes, and a single workspace for metrics and chat.
                </p>
                <ul className="mt-12 grid gap-6 sm:grid-cols-2">
                    {FEATURES.map(({ title, body }) => (
                        <li key={title}>
                            <Card className="h-full rounded-2xl border-border/60 shadow-[0_4px_20px_-2px_rgb(0_0_0/0.06)] transition-[border-color,box-shadow] duration-200 ease-out hover:border-indigo-500/30 dark:shadow-[0_8px_32px_-8px_rgb(0_0_0/0.45)]">
                                <CardHeader>
                                    <CardTitle className="text-base">{title}</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-sm leading-relaxed text-muted-foreground">
                                        {body}
                                    </p>
                                </CardContent>
                            </Card>
                        </li>
                    ))}
                </ul>
            </div>
        </section>
    );
}
