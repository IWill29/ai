import { KeyRound, ShieldCheck, Shuffle, type LucideIcon } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';

const cardClass =
    'rounded-2xl border-border/60 bg-card shadow-[0_4px_20px_-2px_rgb(0_0_0/0.08)] dark:border-border/80 dark:shadow-[0_8px_32px_-8px_rgb(0_0_0/0.55)]';

type TipStep = {
    step: string;
    icon: LucideIcon;
    title: string;
    detail: string;
};

const PASSWORD_TIPS: TipStep[] = [
    {
        step: '1',
        icon: KeyRound,
        title: 'Go long, not tricky',
        detail: 'A 12+ character passphrase is stronger than a short string with symbols.',
    },
    {
        step: '2',
        icon: Shuffle,
        title: 'Keep it unique here',
        detail: 'Don’t reuse a password from another site — your store data deserves its own key.',
    },
    {
        step: '3',
        icon: ShieldCheck,
        title: 'Let a manager help',
        detail: 'Password managers generate and remember strong passwords so you don’t have to.',
    },
];

export default function SecurityPasswordGuide() {
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
                        <ShieldCheck
                            className="size-5 text-indigo-600 dark:text-indigo-400"
                            strokeWidth={2}
                            aria-hidden
                        />
                    </div>
                    <div className="space-y-1">
                        <CardTitle className="text-lg">Choose a strong password</CardTitle>
                        <CardDescription>
                            A few quick habits that keep your account safe without extra hassle.
                        </CardDescription>
                    </div>
                </div>
            </CardHeader>
            <CardContent>
                <ol className="space-y-4">
                    {PASSWORD_TIPS.map(({ step, icon: Icon, title, detail }) => (
                        <li key={step} className="flex gap-3">
                            <span className="flex size-7 shrink-0 items-center justify-center rounded-full border border-indigo-500/25 bg-indigo-500/8 text-xs font-semibold text-indigo-800 dark:text-indigo-200">
                                {step}
                            </span>
                            <div className="min-w-0 space-y-0.5">
                                <div className="flex items-center gap-2">
                                    <Icon
                                        className="size-3.5 shrink-0 text-muted-foreground"
                                        strokeWidth={2}
                                        aria-hidden
                                    />
                                    <p className="text-sm font-medium text-foreground">{title}</p>
                                </div>
                                <p className="text-sm leading-relaxed text-muted-foreground">
                                    {detail}
                                </p>
                            </div>
                        </li>
                    ))}
                </ol>
            </CardContent>
        </Card>
    );
}
