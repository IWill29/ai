type Props = Readonly<{
    minHeight: string;
}>;

export default function SectionPlaceholder({ minHeight }: Props) {
    return <div aria-hidden style={{ minHeight }} />;
}
