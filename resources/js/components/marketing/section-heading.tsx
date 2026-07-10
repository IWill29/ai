type Props = {
    title: string;
    description?: string;
};

export default function SectionHeading({ title, description }: Props) {
    return (
        <div className="mx-auto max-w-2xl text-center">
            <h2 className="text-3xl font-semibold tracking-tight md:text-4xl">{title}</h2>
            {description && (
                <p className="mt-3 text-base leading-relaxed text-muted-foreground md:text-lg">
                    {description}
                </p>
            )}
        </div>
    );
}
