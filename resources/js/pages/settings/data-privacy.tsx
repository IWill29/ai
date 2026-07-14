import { Head } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

type DataProcessingSection = Readonly<{
    heading: string;
    body: string;
}>;

type DataProcessingNote = Readonly<{
    title: string;
    updated_at: string;
    sections: DataProcessingSection[];
}>;

type Props = Readonly<{
    note: DataProcessingNote;
}>;

const cardClass =
    'rounded-2xl border-border/60 bg-card shadow-[0_4px_20px_-2px_rgb(0_0_0/0.08)] dark:border-border/80 dark:shadow-[0_8px_32px_-8px_rgb(0_0_0/0.55)]';

export default function DataPrivacySettings({ note }: Props) {
    return (
        <>
            <Head title="Data privacy" />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 md:gap-8 md:p-6">
                <Card className={cardClass}>
                    <CardHeader className="space-y-2">
                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                            <ShieldCheck className="size-4 text-emerald-600 dark:text-emerald-400" aria-hidden />
                            GDPR processing note
                        </div>
                        <CardTitle>{note.title}</CardTitle>
                        <CardDescription>Last updated {note.updated_at}</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        {note.sections.map((section) => (
                            <section key={section.heading} className="space-y-2">
                                <h2 className="text-sm font-semibold text-foreground">{section.heading}</h2>
                                <p className="text-sm leading-relaxed text-muted-foreground">{section.body}</p>
                            </section>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

DataPrivacySettings.layout = {
    breadcrumbs: [
        {
            title: 'Data privacy',
            href: '/settings/data-privacy',
        },
    ],
};
