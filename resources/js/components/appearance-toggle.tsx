import type { LucideIcon } from 'lucide-react';
import { Monitor, Moon, Sun } from 'lucide-react';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import type { Appearance } from '@/hooks/use-appearance';
import { useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';

const tabs: { value: Appearance; icon: LucideIcon; label: string }[] = [
    { value: 'light', icon: Sun, label: 'Light' },
    { value: 'dark', icon: Moon, label: 'Dark' },
    { value: 'system', icon: Monitor, label: 'System' },
];

export default function AppearanceToggle({
    className,
    showLabels = true,
}: Readonly<{
    className?: string;
    showLabels?: boolean;
}>) {
    const { appearance, updateAppearance } = useAppearance();
    const activeIndex = Math.max(
        0,
        tabs.findIndex((tab) => tab.value === appearance),
    );

    return (
        <TooltipProvider delayDuration={400}>
            <div
                role="tablist"
                aria-label="Theme"
                className={cn(
                    'relative inline-grid grid-cols-3 rounded-xl border border-border/60 bg-muted/50 p-0.5',
                    className,
                )}
            >
                <div
                    aria-hidden
                    className={cn(
                        'pointer-events-none absolute inset-y-0.5 left-0.5 w-[calc((100%-4px)/3)] rounded-[10px]',
                        'bg-background shadow-[0_1px_3px_rgb(0_0_0/0.08)] dark:shadow-[0_1px_4px_rgb(0_0_0/0.35)]',
                        'transition-transform duration-200 ease-[cubic-bezier(0.23,1,0.32,1)] motion-reduce:transition-none',
                    )}
                    style={{
                        transform: `translateX(calc(${activeIndex * 100}%))`,
                    }}
                />

                {tabs.map(({ value, icon: Icon, label }) => {
                    const isActive = appearance === value;

                    return (
                        <Tooltip key={value}>
                            <TooltipTrigger asChild>
                                <button
                                    type="button"
                                    role="tab"
                                    aria-selected={isActive}
                                    aria-label={label}
                                    onClick={() => updateAppearance(value)}
                                    className={cn(
                                        'relative z-10 inline-flex items-center justify-center gap-1 rounded-[10px] px-2 py-1.5',
                                        'text-muted-foreground transition-[color] duration-150 ease-out',
                                        'hover:text-foreground active:scale-[0.97] motion-reduce:active:scale-100',
                                        isActive && 'text-foreground',
                                        showLabels ? 'md:px-2.5' : 'px-2.5',
                                    )}
                                >
                                    <Icon className="size-3.5 shrink-0" strokeWidth={2} />
                                    {showLabels && (
                                        <span className="hidden text-[11px] font-medium md:inline">
                                            {label}
                                        </span>
                                    )}
                                </button>
                            </TooltipTrigger>
                            {!showLabels && (
                                <TooltipContent side="bottom">{label}</TooltipContent>
                            )}
                        </Tooltip>
                    );
                })}
            </div>
        </TooltipProvider>
    );
}
