import type { ReactNode } from 'react';
import { CheckCircle2, ChevronRight, Clock, Loader2, XCircle } from 'lucide-react';
import { toolLabel } from '@/lib/chat-tool-labels';
import { cn } from '@/lib/utils';

type Props = Readonly<{
    toolName: string;
    summary: Record<string, unknown> | null;
}>;

function renderListRows(summary: Record<string, unknown>): ReactNode {
    const rows = summary.rows;

    if (!Array.isArray(rows) || rows.length === 0) {
        return null;
    }

    const displayRows = rows.slice(0, 5);
    const total = typeof summary.total === 'number' ? summary.total : rows.length;

    return (
        <div className="mt-2 overflow-x-auto">
            <table className="w-full text-left text-xs">
                <tbody>
                    {displayRows.map((row, index) => {
                        if (typeof row !== 'object' || row === null) {
                            return null;
                        }

                        const record = row as Record<string, unknown>;
                        const label =
                            String(record.name ?? record.title ?? record.number ?? record.id ?? `#${index + 1}`);

                        return (
                            <tr key={index} className="border-t border-border/40 first:border-t-0">
                                <td className="py-1 pr-2 font-medium">{label}</td>
                                <td className="py-1 text-muted-foreground">
                                    {String(record.status ?? record.total ?? record.quantity ?? '—')}
                                </td>
                            </tr>
                        );
                    })}
                </tbody>
            </table>
            {total > displayRows.length && (
                <p className="mt-1 text-xs text-muted-foreground">
                    and {total - displayRows.length} more
                </p>
            )}
        </div>
    );
}

function renderDetail(summary: Record<string, unknown>): ReactNode {
    const entries = Object.entries(summary).filter(
        ([key, value]) => key !== 'rows' && value !== null && typeof value !== 'object',
    );

    if (entries.length === 0) {
        return null;
    }

    return (
        <dl className="mt-2 grid gap-1 text-xs">
            {entries.slice(0, 6).map(([key, value]) => (
                <div key={key} className="flex justify-between gap-3">
                    <dt className="text-muted-foreground capitalize">{key.replaceAll('_', ' ')}</dt>
                    <dd className="font-medium">{String(value)}</dd>
                </div>
            ))}
        </dl>
    );
}

function renderMetrics(summary: Record<string, unknown>): ReactNode {
    const metrics = summary.metrics;

    if (!Array.isArray(metrics)) {
        return renderDetail(summary);
    }

    return (
        <div className="mt-2 flex flex-wrap gap-2">
            {metrics.slice(0, 4).map((metric, index) => {
                if (typeof metric !== 'object' || metric === null) {
                    return null;
                }

                const record = metric as Record<string, unknown>;

                return (
                    <div
                        key={index}
                        className="rounded-lg border border-border/50 bg-background/60 px-2.5 py-1.5 text-xs"
                    >
                        <p className="text-muted-foreground">{String(record.label ?? 'Metric')}</p>
                        <p className="font-semibold">{String(record.value ?? '—')}</p>
                    </div>
                );
            })}
        </div>
    );
}

export default function ChatStructuredResult({ toolName, summary }: Props) {
    if (summary === null) {
        return null;
    }

    if (toolName.startsWith('list_')) {
        return renderListRows(summary);
    }

    if (toolName === 'get_metrics') {
        return renderMetrics(summary);
    }

    if (toolName.startsWith('get_')) {
        return renderDetail(summary);
    }

    if (typeof summary.message === 'string') {
        return <p className="mt-2 text-xs text-muted-foreground">{summary.message}</p>;
    }

    return renderDetail(summary);
}

type StepCardProps = Readonly<{
    step: App.Domains.Chat.DTOs.ActionStepDTO;
}>;

function StepStatusIcon({ status }: { status: string }) {
    switch (status) {
        case 'running':
            return <Loader2 className="size-3.5 animate-spin text-indigo-500" aria-hidden />;
        case 'done':
            return <CheckCircle2 className="size-3.5 text-emerald-600" aria-hidden />;
        case 'failed':
            return <XCircle className="size-3.5 text-destructive" aria-hidden />;
        case 'awaiting_confirmation':
            return <Clock className="size-3.5 text-amber-600" aria-hidden />;
        default:
            return <ChevronRight className="size-3.5 text-muted-foreground" aria-hidden />;
    }
}

export function ChatActionStepCard({ step }: StepCardProps) {
    return (
        <div
            className={cn(
                'rounded-2xl border border-border/60 bg-card/80 p-3 shadow-sm',
                'motion-safe:animate-in motion-safe:fade-in motion-safe:slide-in-from-bottom-2 motion-safe:duration-200',
            )}
            style={{ animationTimingFunction: 'var(--ease-out-strong)' }}
        >
            <div className="flex items-center gap-2">
                <StepStatusIcon status={step.status} />
                <span className="text-sm font-medium">{toolLabel(step.toolName)}</span>
                {step.durationMs !== null && (
                    <span className="text-xs text-muted-foreground">{step.durationMs}ms</span>
                )}
            </div>

            {step.status === 'done' && (
                <ChatStructuredResult toolName={step.toolName} summary={step.resultSummary} />
            )}

            {step.status === 'failed' && typeof step.resultSummary?.message === 'string' && (
                <p className="mt-2 text-xs text-destructive">{String(step.resultSummary.message)}</p>
            )}

            {step.status === 'awaiting_confirmation' && (
                <p className="mt-2 text-sm text-amber-600 dark:text-amber-400">
                    Waiting for your confirmation…
                </p>
            )}
        </div>
    );
}
