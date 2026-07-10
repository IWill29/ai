import type { LucideIcon } from 'lucide-react';
import {
    ArrowRight,
    Check,
    ChevronRight,
    ExternalLink,
    KeyRound,
    Link2,
    ShieldCheck,
    Sparkles,
    Store,
} from 'lucide-react';
import { useCallback, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';

export type ConnectGuideStep = {
    id: string;
    icon: LucideIcon;
    title: string;
    summary: string;
    hints: string[];
    tip?: string;
    externalHref?: string;
    externalLabel?: string;
};

export const CONNECT_GUIDE_STEPS: ConnectGuideStep[] = [
    {
        id: 'admin',
        icon: Store,
        title: 'Open Shopify admin',
        summary: 'Find the apps section — this is where you’ll create a private connection for AgentStore.',
        hints: [
            'Sign in to the Shopify admin for your store',
            'Go to Settings → Apps and sales channels',
            'Choose Develop apps (or Allow custom app development if prompted)',
        ],
        tip: 'A development store works great if you’re just trying AgentStore.',
        externalHref: 'https://admin.shopify.com/settings/apps/development',
        externalLabel: 'Open Shopify app settings',
    },
    {
        id: 'create-app',
        icon: ShieldCheck,
        title: 'Create a custom app',
        summary: 'Name it AgentStore so you can spot it later. You only need Admin API access — no public listing.',
        hints: [
            'Click Create an app and name it AgentStore',
            'Open Configuration → Admin API integration',
            'Enable the permissions Shopify suggests for store data access',
        ],
        tip: 'You can tighten permissions later; AgentStore only uses what it needs to sync and assist.',
    },
    {
        id: 'install',
        icon: KeyRound,
        title: 'Install & copy keys',
        summary: 'Install the app once, then copy two values — we’ll check them before anything is saved.',
        hints: [
            'Install the app and open API credentials',
            'Copy the Admin API access token (starts with shpat_)',
            'Copy the API secret key for secure webhooks',
        ],
        tip: 'Tokens stay encrypted on our servers. They never appear in chat logs.',
    },
    {
        id: 'connect',
        icon: Link2,
        title: 'Paste credentials below',
        summary: 'Drop your store URL and keys into the form. We validate the connection live before saving.',
        hints: [
            'Use your myshop.myshopify.com domain',
            'Paste the access token and API secret',
            'Click Connect store — sync starts automatically',
        ],
        tip: 'Most merchants finish in about five minutes.',
    },
];

type ConnectOnboardingGuideProps = {
    onReadyForCredentials?: () => void;
};

export default function ConnectOnboardingGuide({
    onReadyForCredentials,
}: ConnectOnboardingGuideProps) {
    const [activeStep, setActiveStep] = useState(0);
    const [completedSteps, setCompletedSteps] = useState<Set<number>>(() => new Set());

    const step = CONNECT_GUIDE_STEPS[activeStep];
    const StepIcon = step.icon;
    const progress = ((activeStep + 1) / CONNECT_GUIDE_STEPS.length) * 100;
    const isLastStep = activeStep === CONNECT_GUIDE_STEPS.length - 1;

    const goToStep = useCallback((index: number) => {
        setActiveStep(Math.max(0, Math.min(index, CONNECT_GUIDE_STEPS.length - 1)));
    }, []);

    const markCompleteAndAdvance = useCallback(() => {
        setCompletedSteps((prev) => new Set(prev).add(activeStep));

        if (isLastStep) {
            onReadyForCredentials?.();

            return;
        }

        setActiveStep((current) => current + 1);
    }, [activeStep, isLastStep, onReadyForCredentials]);

    return (
        <Card
            className={cn(
                'overflow-hidden rounded-2xl border-border/60',
                'shadow-[0_4px_20px_-2px_rgb(0_0_0/0.08)] dark:shadow-[0_8px_32px_-8px_rgb(0_0_0/0.55)]',
            )}
        >
            <CardHeader className="gap-4 border-b border-border/50 bg-muted/20 pb-5">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div className="mb-2 inline-flex items-center gap-2 rounded-full border border-indigo-500/20 bg-indigo-500/8 px-3 py-1">
                            <Sparkles className="size-3.5 text-indigo-600 dark:text-indigo-400" aria-hidden />
                            <span className="text-[11px] font-semibold uppercase tracking-wider text-indigo-800 dark:text-indigo-200">
                                Setup guide
                            </span>
                        </div>
                        <CardTitle className="text-lg">Connect in a few calm steps</CardTitle>
                        <CardDescription className="mt-1 max-w-lg">
                            No coding required. Follow along — you can jump back anytime.
                        </CardDescription>
                    </div>
                    <p className="text-xs font-medium text-muted-foreground">
                        Step {activeStep + 1} of {CONNECT_GUIDE_STEPS.length}
                    </p>
                </div>

                <div
                    className="h-1.5 overflow-hidden rounded-full bg-muted"
                    role="progressbar"
                    aria-valuenow={Math.round(progress)}
                    aria-valuemin={0}
                    aria-valuemax={100}
                    aria-label="Setup progress"
                >
                    <div
                        className="h-full rounded-full bg-indigo-500 transition-[width] duration-300 ease-[cubic-bezier(0.23,1,0.32,1)] motion-reduce:transition-none"
                        style={{ width: `${Math.min(100, progress)}%` }}
                    />
                </div>
            </CardHeader>

            <CardContent className="grid gap-6 p-0 md:grid-cols-[minmax(0,13rem)_1fr]">
                <nav
                    className="flex flex-row gap-2 overflow-x-auto border-b border-border/50 p-4 md:flex-col md:gap-1 md:border-b-0 md:border-r md:p-4"
                    aria-label="Setup steps"
                >
                    {CONNECT_GUIDE_STEPS.map((item, index) => {
                        const done = completedSteps.has(index);
                        const isActive = activeStep === index;
                        const Icon = item.icon;

                        return (
                            <button
                                key={item.id}
                                type="button"
                                onClick={() => goToStep(index)}
                                aria-current={isActive ? 'step' : undefined}
                                className={cn(
                                    'flex min-w-[9.5rem] shrink-0 items-center gap-3 rounded-xl px-3 py-2.5 text-left',
                                    'transition-[background-color,color,box-shadow] duration-150 ease-out',
                                    'hover:bg-muted/80 active:scale-[0.99] motion-reduce:active:scale-100',
                                    isActive &&
                                        'bg-indigo-500/10 text-foreground ring-1 ring-indigo-500/15',
                                    !isActive && 'text-muted-foreground',
                                )}
                            >
                                <span
                                    className={cn(
                                        'flex size-8 shrink-0 items-center justify-center rounded-lg text-xs font-semibold',
                                        done &&
                                            'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300',
                                        !done &&
                                            isActive &&
                                            'bg-indigo-500/15 text-indigo-700 dark:text-indigo-300',
                                        !done &&
                                            !isActive &&
                                            'bg-muted text-muted-foreground',
                                    )}
                                >
                                    {done ? (
                                        <Check className="size-4" strokeWidth={2.5} aria-hidden />
                                    ) : (
                                        index + 1
                                    )}
                                </span>
                                <span className="min-w-0">
                                    <span className="flex items-center gap-1.5 text-sm font-medium leading-tight">
                                        <Icon className="size-3.5 shrink-0 opacity-70" aria-hidden />
                                        <span className="truncate">{item.title}</span>
                                    </span>
                                </span>
                            </button>
                        );
                    })}
                </nav>

                <div className="flex flex-col gap-5 p-5 md:p-6">
                    <div
                        key={step.id}
                        className="motion-safe:animate-in motion-safe:fade-in-0 motion-safe:slide-in-from-right-2 motion-safe:duration-200 motion-reduce:animate-none"
                    >
                        <div className="mb-4 flex size-11 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                            <StepIcon className="size-5" strokeWidth={2} aria-hidden />
                        </div>
                        <h3 className="text-base font-semibold tracking-tight text-foreground">
                            {step.title}
                        </h3>
                        <p className="mt-1.5 text-sm leading-relaxed text-muted-foreground">
                            {step.summary}
                        </p>

                        <ul className="mt-4 space-y-2.5">
                            {step.hints.map((hint) => (
                                <li
                                    key={hint}
                                    className="flex gap-2.5 text-sm leading-relaxed text-foreground/90"
                                >
                                    <span
                                        className="mt-2 size-1.5 shrink-0 rounded-full bg-indigo-500/70"
                                        aria-hidden
                                    />
                                    {hint}
                                </li>
                            ))}
                        </ul>

                        {step.tip ? (
                            <p className="mt-4 rounded-xl border border-emerald-500/20 bg-emerald-500/8 px-3.5 py-2.5 text-sm text-emerald-900 dark:text-emerald-200">
                                {step.tip}
                            </p>
                        ) : null}

                        {step.externalHref ? (
                            <Button
                                variant="outline"
                                size="sm"
                                asChild
                                className="mt-4 rounded-xl"
                            >
                                <a
                                    href={step.externalHref}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    {step.externalLabel}
                                    <ExternalLink className="size-3.5" aria-hidden />
                                </a>
                            </Button>
                        ) : null}
                    </div>

                    <div className="mt-auto flex flex-wrap items-center justify-end gap-2 border-t border-border/50 pt-4">
                        {!isLastStep ? (
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                className="rounded-xl text-muted-foreground"
                                onClick={() => goToStep(activeStep + 1)}
                            >
                                Skip for now
                                <ChevronRight className="size-4" aria-hidden />
                            </Button>
                        ) : null}
                        <Button
                            type="button"
                            variant="brand"
                            size="sm"
                            className="rounded-xl"
                            onClick={markCompleteAndAdvance}
                        >
                            {isLastStep ? 'Go to credentials' : 'Done — next step'}
                            <ArrowRight className="size-4" aria-hidden />
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
