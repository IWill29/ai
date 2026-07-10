import { ExternalLink, KeyRound, Sparkles, UserPlus, type LucideIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';

const OPENROUTER_KEYS_URL = 'https://openrouter.ai/keys';

const cardClass =
    'rounded-2xl border-border/60 bg-card shadow-[0_4px_20px_-2px_rgb(0_0_0/0.08)] dark:border-border/80 dark:shadow-[0_8px_32px_-8px_rgb(0_0_0/0.55)]';

type GuideStep = {
    step: string;
    icon: LucideIcon;
    title: string;
    detail: string;
};

const GUIDE_STEPS: GuideStep[] = [
    {
        step: '1',
        icon: UserPlus,
        title: 'Create a free account',
        detail: 'Sign up at OpenRouter — you only pay for what the models use, directly with them.',
    },
    {
        step: '2',
        icon: KeyRound,
        title: 'Generate an API key',
        detail: 'Open Keys in your dashboard and create a key. Copy it — you’ll paste it here next.',
    },
    {
        step: '3',
        icon: Sparkles,
        title: 'Paste & validate below',
        detail: 'We test the key live before saving. Your key stays encrypted and never appears in chat.',
    },
];

export default function AiKeysGuide() {
    return (
        <Card
            className={cn(
                cardClass,
                'motion-safe:transition-[opacity,transform] motion-safe:duration-200 motion-safe:ease-out',
                'motion-safe:starting:opacity-0 motion-safe:starting:translate-y-2',
            )}
        >
            <CardHeader className="pb-4">
                <div className="flex items-start gap-3">
                    <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-indigo-500/10">
                        <Sparkles
                            className="size-5 text-indigo-600 dark:text-indigo-400"
                            strokeWidth={2}
                            aria-hidden
                        />
                    </div>
                    <div className="space-y-1">
                        <CardTitle className="text-lg">How to get your key</CardTitle>
                        <CardDescription>
                            Three quick steps — most people finish in under two minutes.
                        </CardDescription>
                    </div>
                </div>
            </CardHeader>
            <CardContent className="space-y-5">
                <ol className="space-y-4">
                    {GUIDE_STEPS.map(({ step, icon: Icon, title, detail }) => (
                        <li key={step} className="flex gap-3">
                            <div className="flex flex-col items-center gap-1">
                                <span className="flex size-7 shrink-0 items-center justify-center rounded-full border border-indigo-500/25 bg-indigo-500/8 text-xs font-semibold text-indigo-800 dark:text-indigo-200">
                                    {step}
                                </span>
                                <span className="hidden h-full w-px bg-border/60 sm:block" aria-hidden />
                            </div>
                            <div className="min-w-0 space-y-0.5 pb-1">
                                <div className="flex items-center gap-2">
                                    <Icon
                                        className="size-3.5 shrink-0 text-muted-foreground"
                                        strokeWidth={2}
                                        aria-hidden
                                    />
                                    <p className="text-sm font-medium text-foreground">{title}</p>
                                </div>
                                <p className="text-sm leading-relaxed text-muted-foreground">{detail}</p>
                            </div>
                        </li>
                    ))}
                </ol>

                <Button variant="outline" asChild className="w-full rounded-xl sm:w-auto">
                    <a href={OPENROUTER_KEYS_URL} target="_blank" rel="noopener noreferrer">
                        Open OpenRouter keys
                        <ExternalLink className="size-4" aria-hidden />
                    </a>
                </Button>
            </CardContent>
        </Card>
    );
}
