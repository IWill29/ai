import { Link } from '@inertiajs/react';
import ChatMockup from '@/components/marketing/chat-mockup';
import { Button } from '@/components/ui/button';
import { register } from '@/routes';

export default function HeroSection() {
    return (
        <section className="relative overflow-x-hidden px-4 pb-16 pt-12 md:pb-24 md:pt-20">
            <div
                aria-hidden
                className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(99,102,241,0.12),transparent_60%)]"
            />

            <div className="relative mx-auto flex w-full max-w-[var(--landing-max-width)] flex-col items-center">
                <div
                    className="w-full text-center"
                    style={{ transitionTimingFunction: 'var(--ease-out-strong)' }}
                >
                    <h1 className="mx-auto max-w-4xl text-4xl font-semibold tracking-tight md:text-5xl">
                        Run your Shopify store with AI
                    </h1>
                    <p className="mx-auto mt-4 max-w-2xl text-lg leading-relaxed text-muted-foreground">
                        Connect your store, ask in plain English, and let the agent handle orders,
                        products, and inventory — with confirmation before every write.
                    </p>
                    <div className="mx-auto mt-8 flex w-full max-w-md flex-wrap justify-center gap-3">
                        <Button
                            asChild
                            size="lg"
                            variant="brand"
                            className="rounded-full px-8 active:scale-[0.97] motion-reduce:active:scale-100"
                        >
                            <Link href={register()}>Get started free</Link>
                        </Button>
                        <Button asChild size="lg" variant="outline" className="rounded-xl">
                            <a href="#features">See features</a>
                        </Button>
                    </div>
                </div>

                <div className="mt-10 w-full min-w-0 sm:mt-12 lg:mt-14">
                    <ChatMockup />
                </div>
            </div>
        </section>
    );
}
