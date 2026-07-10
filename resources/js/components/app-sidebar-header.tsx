import type { ReactNode } from 'react';
import AppearanceToggle from '@/components/appearance-toggle';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { cn } from '@/lib/utils';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

function sectionEyebrow(pageTitle: string): string {
    switch (pageTitle) {
        case 'Stores':
        case 'Connect':
        case 'Connect store':
            return 'Commerce';
        case 'AI keys':
        case 'OpenRouter':
        case 'Billing':
        case 'Profile':
        case 'Security':
            return 'Account';
        default:
            return 'Workspace';
    }
}

function renderHeaderTitle(
    breadcrumbs: BreadcrumbItemType[],
    currentPage: BreadcrumbItemType | undefined,
): ReactNode {
    const showTitleBlock = breadcrumbs.length === 1 && currentPage;

    if (showTitleBlock) {
        return (
            <div className="min-w-0">
                <p className="text-[10px] font-semibold uppercase tracking-[0.14em] text-muted-foreground/75">
                    {sectionEyebrow(currentPage.title)}
                </p>
                <h1
                    className={cn(
                        'truncate text-base font-semibold tracking-tight',
                        'text-foreground transition-colors duration-150 ease-out',
                    )}
                >
                    {currentPage.title}
                </h1>
            </div>
        );
    }

    if (breadcrumbs.length > 1) {
        return <Breadcrumbs breadcrumbs={breadcrumbs} />;
    }

    if (currentPage) {
        return (
            <h1
                className={cn(
                    'truncate text-base font-semibold tracking-tight',
                    'text-foreground transition-colors duration-150 ease-out',
                )}
            >
                {currentPage.title}
            </h1>
        );
    }

    return null;
}

export function AppSidebarHeader({
    breadcrumbs = [],
}: Readonly<{
    breadcrumbs?: BreadcrumbItemType[];
}>) {
    const currentPage = breadcrumbs.at(-1);

    return (
        <header
            className={cn(
                'flex h-14 shrink-0 items-center justify-between gap-3 border-b border-border/40 px-4 md:px-5',
                'bg-background/85 backdrop-blur-sm supports-[backdrop-filter]:bg-background/70',
            )}
        >
            <div className="flex min-w-0 items-center gap-3">
                <SidebarTrigger
                    className={cn(
                        'size-8 shrink-0 rounded-lg border border-border/60 bg-background text-muted-foreground shadow-sm',
                        'transition-[background-color,color,transform] duration-150 ease-out',
                        'hover:bg-muted hover:text-foreground active:scale-[0.97] motion-reduce:active:scale-100',
                    )}
                />

                {renderHeaderTitle(breadcrumbs, currentPage)}
            </div>

            <AppearanceToggle className="shrink-0" />
        </header>
    );
}
