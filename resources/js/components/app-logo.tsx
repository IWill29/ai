import AppLogoIcon from '@/components/app-logo-icon';
import { cn } from '@/lib/utils';

const appName = import.meta.env.VITE_APP_NAME || 'AgentStore';

function Wordmark() {
    if (appName === 'AgentStore') {
        return (
            <>
                <span className="font-medium text-foreground/88">Agent</span>
                <span>Store</span>
            </>
        );
    }

    return appName;
}

export default function AppLogo({ compact = false }: { compact?: boolean }) {
    return (
        <div
            className={cn(
                'flex min-w-0 items-center',
                compact ? 'justify-center' : 'gap-3',
            )}
        >
            <div
                className={cn(
                    'relative shrink-0 overflow-hidden rounded-[18px]',
                    'shadow-[0_1px_2px_rgb(0_0_0/0.05),0_4px_14px_-3px_rgb(67_56_202/0.38)]',
                    'dark:shadow-[0_2px_12px_rgb(0_0_0/0.55),0_0_0_1px_rgb(255_255_255/0.07)]',
                    compact ? 'size-8' : 'size-9',
                )}
            >
                <AppLogoIcon />
            </div>

            {!compact && (
                <div className="grid min-w-0 flex-1 gap-1 text-left leading-none">
                    <p className="truncate text-[15px] font-semibold tracking-[-0.03em] text-foreground transition-colors duration-150 ease-out">
                        <Wordmark />
                    </p>
                    <p className="truncate text-[10px] font-medium uppercase tracking-[0.16em] text-muted-foreground transition-colors duration-150 ease-out">
                        Commerce AI
                    </p>
                </div>
            )}
        </div>
    );
}
