import { Link } from '@inertiajs/react';
import {
    CreditCard,
    LayoutGrid,
    MessageSquare,
    Plus,
    ShieldCheck,
    Sparkles,
    Store,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Button } from '@/components/ui/button';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarSeparator,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as chatIndex } from '@/routes/chat';
import { billing, openrouter } from '@/routes/settings';
import { connect, index as storesIndex } from '@/routes/stores';
import type { NavItem } from '@/types';

const workspaceNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Chat',
        href: chatIndex(),
        icon: MessageSquare,
    },
];

const commerceNavItems: NavItem[] = [
    {
        title: 'Stores',
        href: storesIndex(),
        icon: Store,
    },
];

const settingsNavItems: NavItem[] = [
    {
        title: 'AI keys',
        href: openrouter(),
        icon: Sparkles,
    },
    {
        title: 'Data privacy',
        href: '/settings/data-privacy',
        icon: ShieldCheck,
    },
    {
        title: 'Billing',
        href: billing(),
        icon: CreditCard,
    },
];

export function AppSidebar() {
    return (
        <Sidebar
            collapsible="icon"
            variant="inset"
            className="[&_[data-sidebar=sidebar]]:rounded-2xl [&_[data-sidebar=sidebar]]:border [&_[data-sidebar=sidebar]]:border-sidebar-border/70 [&_[data-sidebar=sidebar]]:shadow-[0_4px_20px_-2px_rgb(0_0_0/0.06)] dark:[&_[data-sidebar=sidebar]]:shadow-[0_8px_32px_-8px_rgb(0_0_0/0.55)]"
        >
            <SidebarHeader className="p-2">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            size="lg"
                            asChild
                            className="h-auto rounded-xl px-2.5 py-2.5 hover:bg-sidebar-accent/70 group-data-[collapsible=icon]:size-10! group-data-[collapsible=icon]:p-0!"
                        >
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent className="gap-1">
                <NavMain items={workspaceNavItems} label="Workspace" />
                <SidebarSeparator className="mx-3 bg-sidebar-border/60" />
                <NavMain items={commerceNavItems} label="Commerce" />
                <SidebarSeparator className="mx-3 bg-sidebar-border/60" />
                <NavMain items={settingsNavItems} label="Account" />
            </SidebarContent>

            <SidebarFooter className="gap-2 p-2">
                <Button
                    asChild
                    size="sm"
                    variant="brand"
                    className="w-full rounded-full group-data-[collapsible=icon]:size-8 group-data-[collapsible=icon]:rounded-xl group-data-[collapsible=icon]:p-0"
                >
                    <Link href={connect()} prefetch className="gap-2">
                        <Plus className="size-4 group-data-[collapsible=icon]:size-4" />
                        <span className="group-data-[collapsible=icon]:hidden">
                            Connect store
                        </span>
                    </Link>
                </Button>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
