export type FaqItem = {
    q: string;
    a: string;
};

type Props = {
    faqs: FaqItem[];
};

export default function FaqSection({ faqs }: Props) {
    return (
        <section id="faq" className="scroll-mt-20 px-4 py-16 md:py-24">
            <div className="mx-auto max-w-3xl">
                <h2 className="text-center text-3xl font-semibold tracking-tight">
                    Frequently asked questions
                </h2>
                <div className="mt-10 space-y-3">
                    {faqs.map(({ q, a }) => (
                        <details
                            key={q}
                            className="group rounded-2xl border border-border/60 bg-card/50 px-4 py-1 open:shadow-sm"
                        >
                            <summary className="cursor-pointer list-none py-3 text-sm font-medium marker:content-none [&::-webkit-details-marker]:hidden">
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
                            <p className="pb-4 text-sm leading-relaxed text-muted-foreground">{a}</p>
                        </details>
                    ))}
                </div>
            </div>
        </section>
    );
}
