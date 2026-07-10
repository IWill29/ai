import { Link } from '@inertiajs/react';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn } from '@/lib/utils';
import type { NavItem } from '@/types';

export function NavMain({
    items = [],
    label = 'Platform',
}: Readonly<{
    items?: NavItem[];
    label?: string;
}>) {
    const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel className="px-2 text-[10px] font-semibold uppercase tracking-[0.14em] text-muted-foreground/75">
                {label}
            </SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => {
                    const active =
                        item.isActive ??
                        (isCurrentUrl(item.href) || isCurrentOrParentUrl(item.href));

                    return (
                        <SidebarMenuItem key={item.title}>
                            <SidebarMenuButton
                                asChild
                                isActive={active}
                                tooltip={{ children: item.title }}
                                className={cn(
                                    'h-9 rounded-xl transition-[background-color,color,box-shadow] duration-150 ease-out',
                                    active &&
                                        'bg-indigo-500/10 font-medium text-indigo-700 shadow-none ring-1 ring-indigo-500/15 hover:bg-indigo-500/12 hover:text-indigo-700 dark:text-indigo-300 dark:hover:text-indigo-200',
                                )}
                            >
                                <Link href={item.href} prefetch>
                                    {item.icon && (
                                        <item.icon
                                            className={cn(
                                                active && 'text-indigo-600 dark:text-indigo-400',
                                            )}
                                        />
                                    )}
                                    <span>{item.title}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}
