type Props = {
    minHeight: string;
};

export default function SectionPlaceholder({ minHeight }: Props) {
    return <div aria-hidden style={{ minHeight }} />;
}
