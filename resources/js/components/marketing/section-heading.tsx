type Props = {
    title: string;
    description?: string;
};

export default function SectionHeading({ title, description }: Props) {
    return (
        <div className="mx-auto max-w-2xl text-center">
            <h2 className="text-2xl font-semibold tracking-tight sm:text-3xl md:text-4xl">{title}</h2>
            {description && (
                <p className="mt-2 text-sm leading-relaxed text-muted-foreground sm:mt-3 sm:text-base md:text-lg">
                    {description}
                </p>
            )}
        </div>
    );
}
