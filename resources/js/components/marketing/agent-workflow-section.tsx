import {
    CheckCircle2,
    Link2,
    Lock,
    MessageSquare,
    Search
    
} from 'lucide-react';
import type {LucideIcon} from 'lucide-react';
import AgentStepMockup from '@/components/marketing/agent-step-mockup';
import SectionHeading from '@/components/marketing/section-heading';
import { Badge } from '@/components/ui/badge';
import { useInViewOnce } from '@/hooks/use-in-view-once';
import { useScrollSpySteps } from '@/hooks/use-scroll-spy-steps';
import { cn } from '@/lib/utils';

export type AgentStep = {
    title: string;
    body: string;
    tools: string[];
    kind: 'setup' | 'chat' | 'read' | 'trace' | 'write';
};

type Props = Readonly<{
    steps: AgentStep[];
}>;

const KIND_META: Record<
    AgentStep['kind'],
    { icon: LucideIcon; label: string; badgeClass: string }
> = {
    setup: {
        icon: Link2,
        label: 'Setup',
        badgeClass: 'bg-sky-500/10 text-sky-700 ring-sky-500/20 dark:text-sky-300',
    },
    chat: {
        icon: MessageSquare,
        label: 'Chat',
        badgeClass: 'bg-indigo-500/10 text-indigo-700 ring-indigo-500/20 dark:text-indigo-300',
    },
    read: {
        icon: Search,
        label: 'Read tools',
        badgeClass: 'bg-emerald-500/10 text-emerald-700 ring-emerald-500/20 dark:text-emerald-300',
    },
    trace: {
        icon: CheckCircle2,
        label: 'Trace',
        badgeClass: 'bg-violet-500/10 text-violet-700 ring-violet-500/20 dark:text-violet-300',
    },
    write: {
        icon: Lock,
        label: 'Write tools · Confirm required',
        badgeClass: 'bg-amber-500/10 text-amber-800 ring-amber-500/25 dark:text-amber-200',
    },
};

type WorkflowStepProps = Readonly<{
    step: AgentStep;
    index: number;
    isLast: boolean;
    inView: boolean;
    isActive: boolean;
    onActivate: () => void;
    stepRef: (element: HTMLElement | null) => void;
}>;

function WorkflowStep({
    step,
    index,
    isLast,
    inView,
    isActive,
    onActivate,
    stepRef,
}: WorkflowStepProps) {
    const meta = KIND_META[step.kind];
    const Icon = meta.icon;

    return (
        <li
            ref={stepRef}
            className={cn(
                'relative grid grid-cols-[auto_1fr] gap-x-4 sm:gap-x-6',
                inView ? 'opacity-100' : 'opacity-0',
                'motion-safe:transition-opacity motion-safe:duration-500',
                'motion-reduce:opacity-100 motion-reduce:transition-none',
            )}
            style={{
                transitionTimingFunction: 'var(--ease-out-strong)',
                transitionDelay: inView ? `${index * 80}ms` : '0ms',
            }}
        >
            <div className="flex flex-col items-center">
                <button
                    type="button"
                    onClick={onActivate}
                    aria-current={isActive ? 'step' : undefined}
                    className={cn(
                        'flex size-10 shrink-0 items-center justify-center rounded-xl ring-1 transition-[box-shadow,ring-color] duration-200 ease-out',
                        meta.badgeClass,
                        isActive && 'ring-2 ring-indigo-500/50 shadow-[0_0_0_4px_rgb(99_102_241/0.12)]',
                    )}
                >
                    <Icon className="size-4" strokeWidth={2} aria-hidden />
                </button>
                {!isLast && (
                    <div
                        aria-hidden
                        className={cn(
                            'mt-2 w-px flex-1 min-h-[3rem] transition-colors duration-300',
                            isActive ? 'bg-indigo-500/40' : 'bg-gradient-to-b from-border/80 to-border/20',
                        )}
                    />
                )}
            </div>

            <div className="pb-8 sm:pb-10">
                <button
                    type="button"
                    onClick={onActivate}
                    className="w-full rounded-xl text-left outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/50"
                >
                    <div className="flex flex-wrap items-center gap-2">
                        <h3
                            className={cn(
                                'text-base font-semibold tracking-tight sm:text-lg',
                                isActive && 'text-indigo-700 dark:text-indigo-300',
                            )}
                        >
                            {step.title}
                        </h3>
                        <Badge
                            variant="outline"
                            className={cn('rounded-lg text-[10px] font-medium', meta.badgeClass)}
                        >
                            {meta.label}
                        </Badge>
                    </div>
                    <p className="mt-2 max-w-xl text-sm leading-relaxed text-muted-foreground sm:text-base">
                        {step.body}
                    </p>
                </button>

                {step.tools.length > 0 && (
                    <ul className="mt-3 flex flex-wrap gap-1.5" aria-label={`Tools for ${step.title}`}>
                        {step.tools.map((tool) => (
                            <li key={tool}>
                                <code className="rounded-md border border-border/60 bg-muted/40 px-2 py-0.5 font-mono text-[11px] text-foreground/90">
                                    {tool}
                                </code>
                            </li>
                        ))}
                    </ul>
                )}

                {isActive && (
                    <div className="mt-4 lg:hidden" aria-live="polite" aria-label={`Preview: ${step.title}`}>
                        <p className="mb-2 text-[10px] font-medium uppercase tracking-[0.14em] text-muted-foreground">
                            Live preview
                        </p>
                        <AgentStepMockup kind={step.kind} />
                    </div>
                )}
            </div>
        </li>
    );
}

type MockupPanelProps = Readonly<{
    steps: AgentStep[];
    activeIndex: number;
    inView: boolean;
}>;

function StickyMockupPanel({ steps, activeIndex, inView }: MockupPanelProps) {
    const activeStep = steps[activeIndex] ?? steps[0];

    if (!activeStep) {
        return null;
    }

    return (
        <div
            className={cn(
                'sticky top-20 hidden max-h-[calc(100vh-6rem)] lg:block',
                inView ? 'opacity-100' : 'opacity-0',
                'motion-safe:transition-opacity motion-safe:duration-500',
                'motion-reduce:opacity-100',
            )}
            aria-live="polite"
            aria-label={`Preview: ${activeStep.title}`}
        >
            <p className="mb-3 text-center text-xs font-medium uppercase tracking-[0.14em] text-muted-foreground">
                Live preview · Step {activeIndex + 1} of {steps.length}
            </p>
            <div className="relative min-h-[20rem] sm:min-h-[22rem]">
                <AgentStepMockup key={activeStep.kind} kind={activeStep.kind} />
            </div>
        </div>
    );
}

export default function AgentWorkflowSection({ steps }: Props) {
    const { ref, inView } = useInViewOnce<HTMLElement>();
    const { activeIndex, setActiveIndex, setStepRef } = useScrollSpySteps(steps.length);

    return (
        <section
            id="how-it-works"
            ref={ref}
            className="scroll-mt-20 border-y border-border/40 bg-muted/15 px-4 py-14 sm:py-16 md:py-24"
        >
            <div className="mx-auto max-w-[var(--landing-max-width)]">
                <SectionHeading
                    title="How the agent works"
                    description="Scroll through each step — the preview updates so you can see exactly what happens in the app."
                />

                <div className="mt-8 grid gap-8 sm:mt-10 lg:grid-cols-[minmax(0,1fr)_minmax(0,26rem)] lg:items-start lg:gap-12 xl:grid-cols-[minmax(0,1fr)_minmax(0,28rem)]">
                    <ol className="relative list-none pl-0">
                        {steps.map((step, index) => (
                            <WorkflowStep
                                key={step.title}
                                step={step}
                                index={index}
                                isLast={index === steps.length - 1}
                                inView={inView}
                                isActive={activeIndex === index}
                                onActivate={() => setActiveIndex(index)}
                                stepRef={setStepRef(index)}
                            />
                        ))}
                    </ol>

                    <StickyMockupPanel steps={steps} activeIndex={activeIndex} inView={inView} />
                </div>
            </div>
        </section>
    );
}
