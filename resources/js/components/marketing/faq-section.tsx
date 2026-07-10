import SectionHeading from '@/components/marketing/section-heading';
import { useInViewOnce } from '@/hooks/use-in-view-once';
import { cn } from '@/lib/utils';

export type FaqItem = {
    q: string;
    a: string;
};

type Props = {
    faqs: FaqItem[];
};

export default function FaqSection({ faqs }: Props) {
    const { ref, inView } = useInViewOnce<HTMLElement>();

    return (
        <section id="faq" ref={ref} className="scroll-mt-20 px-4 py-16 md:py-24">
            <div className="mx-auto max-w-3xl">
                <SectionHeading title="Frequently asked questions" />

                <div className="mt-10 space-y-3">
                    {faqs.map(({ q, a }, index) => (
                        <details
                            key={q}
                            className={cn(
                                'group rounded-2xl border border-border/60 bg-card/50 px-4 py-1 open:border-indigo-500/25 open:bg-card/80 open:shadow-sm',
                                inView ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2',
                                'motion-safe:transition-[opacity,transform,border-color,background-color] motion-safe:duration-500',
                                'motion-reduce:opacity-100 motion-reduce:translate-y-0 motion-reduce:transition-none',
                            )}
                            style={{
                                transitionTimingFunction: 'var(--ease-out-strong)',
                                transitionDelay: inView ? `${index * 50}ms` : '0ms',
                            }}
                        >
                            <summary className="cursor-pointer list-none py-3.5 text-sm font-medium marker:content-none sm:text-base [&::-webkit-details-marker]:hidden">
                                <span className="flex items-center justify-between gap-4">
                                    {q}
                                    <span
                                        aria-hidden
                                        className="text-muted-foreground transition-transform duration-200 ease-out group-open:rotate-45"
                                    >
                                        +
                                    </span>
                                </span>
                            </summary>
                            <p className="pb-4 text-sm leading-relaxed text-muted-foreground sm:text-base">{a}</p>
                        </details>
                    ))}
                </div>
            </div>
        </section>
    );
}
