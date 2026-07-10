import { Link } from '@inertiajs/react';
import { lazy, Suspense } from 'react';
import { Button } from '@/components/ui/button';
import { useHideLandingLcpFallback } from '@/lib/landing-lcp-fallback';
import { MARKETING_ROUTES } from '@/lib/marketing-routes';

const ChatMockup = lazy(() => import('@/components/marketing/chat-mockup'));

const MOCKUP_MIN_HEIGHT = 'min-h-[22rem] lg:min-h-[26rem]';

export default function HeroSection() {
    useHideLandingLcpFallback();

    return (
        <section className="relative overflow-x-hidden px-4 pb-12 pt-10 sm:pb-16 sm:pt-12 md:pb-24 md:pt-20">
            <div
                aria-hidden
                className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(99,102,241,0.12),transparent_60%)]"
            />

            <div className="relative mx-auto flex w-full max-w-[var(--landing-max-width)] flex-col items-center">
                <div className="w-full text-center">
                    <h1 className="mx-auto max-w-4xl text-3xl font-semibold tracking-tight sm:text-4xl md:text-5xl">
                        Run your Shopify store with AI
                    </h1>
                    <p className="mx-auto mt-3 max-w-2xl text-base leading-relaxed text-muted-foreground sm:mt-4 sm:text-lg">
                        Connect your store, ask in plain English, and let the agent handle orders,
                        products, and inventory — with confirmation before every write.
                    </p>
                    <div className="mx-auto mt-6 flex w-full max-w-md flex-col justify-center gap-3 sm:mt-8 sm:flex-row sm:flex-wrap">
                        <Button
                            asChild
                            size="lg"
                            variant="brand"
                            className="w-full rounded-full px-8 active:scale-[0.97] motion-reduce:active:scale-100 sm:w-auto"
                        >
                            <Link href={MARKETING_ROUTES.register} prefetch>
                                Get started free
                            </Link>
                        </Button>
                        <Button asChild size="lg" variant="outline" className="w-full rounded-xl sm:w-auto">
                            <a href="#features">See features</a>
                        </Button>
                    </div>
                </div>

                <div className={`mt-8 w-full min-w-0 sm:mt-10 lg:mt-14 ${MOCKUP_MIN_HEIGHT}`}>
                    <Suspense fallback={<div className={`w-full ${MOCKUP_MIN_HEIGHT}`} aria-hidden />}>
                        <ChatMockup />
                    </Suspense>
                </div>
            </div>
        </section>
    );
}
