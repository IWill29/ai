import { Deferred, Head, Link, router, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowUpRight,
    Package,
    ShoppingCart,
    TrendingUp,
    Users,
} from 'lucide-react';
import { useTransition } from 'react';
import StoreSetupEmptyState from '@/components/stores/store-setup-empty-state';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { formatMoney } from '@/lib/money';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';

type DashboardMetricsDTO = App.Domains.Dashboard.DTOs.DashboardMetricsDTO;
type TopProductRowDTO = App.Domains.Dashboard.DTOs.TopProductRowDTO;
type RecentOrderRowDTO = App.Domains.Dashboard.DTOs.RecentOrderRowDTO;

type StoreItem = {
    id: string;
    name: string;
    domain: string;
    status: string;
    lastSyncedAt: string | null;
};

type Filters = {
    store_id: string;
    range: string;
    from: string;
    to: string;
};

type PageProps = {
    hasStores: boolean;
    stores: StoreItem[];
    filters?: Filters;
    lastSyncedAt?: string | null;
    selectedStoreStatus?: string;
    metrics?: DashboardMetricsDTO;
    topProducts?: TopProductRowDTO[];
    recentOrders?: RecentOrderRowDTO[];
};

const RANGE_OPTIONS = [
    { value: '7d', label: 'Last 7 days' },
    { value: '30d', label: 'Last 30 days' },
    { value: '90d', label: 'Last 90 days' },
    { value: 'month', label: 'This month' },
] as const;

const dashboardCardClass =
    'rounded-2xl border-border/60 bg-card shadow-[0_4px_20px_-2px_rgb(0_0_0/0.08)] dark:border-border/80 dark:shadow-[0_8px_32px_-8px_rgb(0_0_0/0.55)]';

function formatDateTime(value: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

function isStale(lastSyncedAt: string | null | undefined): boolean {
    if (!lastSyncedAt) {
        return false;
    }

    const synced = new Date(lastSyncedAt).getTime();
    const dayAgo = Date.now() - 24 * 60 * 60 * 1000;

    return synced < dayAgo;
}

function ChangeBadge({ percent }: Readonly<{ percent: number }>) {
    const positive = percent >= 0;

    return (
        <span className={positive ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500'}>
            {positive ? '+' : ''}
            {percent}% vs previous period
        </span>
    );
}

function KpiSkeletonGrid() {
    return (
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            {Array.from({ length: 5 }).map((_, index) => (
                <Card key={index} className={dashboardCardClass}>
                    <CardHeader className="pb-2">
                        <Skeleton className="h-4 w-24" />
                    </CardHeader>
                    <CardContent className="space-y-2">
                        <Skeleton className="h-8 w-32" />
                        <Skeleton className="h-3 w-40" />
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}

function TableSkeleton() {
    return (
        <Card className={dashboardCardClass}>
            <CardHeader>
                <Skeleton className="h-5 w-40" />
            </CardHeader>
            <CardContent className="space-y-3">
                {Array.from({ length: 5 }).map((_, index) => (
                    <Skeleton key={index} className="h-10 w-full" />
                ))}
            </CardContent>
        </Card>
    );
}

function KpiCard({
    title,
    value,
    subtitle,
    icon: Icon,
    children,
}: {
    title: string;
    value: string | number;
    subtitle?: string;
    icon: typeof TrendingUp;
    children?: React.ReactNode;
}) {
    return (
        <Card className={dashboardCardClass}>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
                <CardTitle className="text-sm font-medium text-muted-foreground">{title}</CardTitle>
                <Icon className="size-4 text-indigo-500" />
            </CardHeader>
            <CardContent>
                <p className="text-2xl font-semibold tracking-tight">{value}</p>
                {subtitle && <p className="mt-1 text-sm text-muted-foreground">{subtitle}</p>}
                {children && <div className="mt-2 text-xs">{children}</div>}
            </CardContent>
        </Card>
    );
}

function ActionableKpiCard({
    title,
    value,
    chatPrompt,
    storeId,
    icon: Icon,
}: {
    title: string;
    value: string | number;
    chatPrompt: string;
    storeId: string;
    icon: typeof Package;
}) {
    const openChat = () => {
        router.get('/chat', {
            store_id: storeId,
            prompt: chatPrompt,
        });
    };

    return (
        <button
            type="button"
            onClick={openChat}
            className={cn(
                dashboardCardClass,
                'text-left transition-[box-shadow,transform] duration-150 ease-out',
                'hover:shadow-[0_8px_24px_-4px_rgb(0_0_0/0.12)] dark:hover:shadow-[0_12px_36px_-8px_rgb(0_0_0/0.65)]',
                'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/50',
                'active:scale-[0.99] motion-reduce:active:scale-100',
            )}
        >
            <div className="flex flex-row items-center justify-between p-6 pb-2">
                <span className="text-sm font-medium text-muted-foreground">{title}</span>
                <Icon className="size-4 text-indigo-500" />
            </div>
            <div className="px-6 pb-6">
                <p className="text-2xl font-semibold tracking-tight">{value}</p>
                <span className="mt-2 inline-flex items-center gap-1 text-xs text-indigo-600 dark:text-indigo-400">
                    Ask agent
                    <ArrowUpRight className="size-3" />
                </span>
            </div>
        </button>
    );
}

function KpiGrid() {
    const { metrics, filters } = usePage<PageProps>().props;

    if (!metrics || !filters) {
        return null;
    }

    return (
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <KpiCard title="Revenue" value={formatMoney(metrics.revenueMinor, metrics.currency)} icon={TrendingUp}>
                <ChangeBadge percent={metrics.revenueChangePercent} />
            </KpiCard>
            <KpiCard
                title="Orders"
                value={metrics.ordersCount}
                subtitle={`AOV ${formatMoney(metrics.averageOrderValueMinor, metrics.currency)}`}
                icon={ShoppingCart}
            >
                <ChangeBadge percent={metrics.ordersChangePercent} />
            </KpiCard>
            <KpiCard
                title="Customers"
                value={`${metrics.newCustomers} new`}
                subtitle={`${metrics.returningCustomers} returning`}
                icon={Users}
            />
            <ActionableKpiCard
                title="Unfulfilled"
                value={metrics.unfulfilledOrders}
                chatPrompt="Show all unfulfilled orders and help me fulfill them"
                storeId={filters.store_id}
                icon={ShoppingCart}
            />
            <ActionableKpiCard
                title="Stock alerts"
                value={`${metrics.lowStockProducts} low / ${metrics.outOfStockProducts} out`}
                chatPrompt="Show low-stock and out-of-stock products"
                storeId={filters.store_id}
                icon={Package}
            />
        </div>
    );
}

function TopProductsTable() {
    const { topProducts } = usePage<PageProps>().props;

    if (!topProducts) {
        return null;
    }

    return (
        <Card className={dashboardCardClass}>
            <CardHeader>
                <CardTitle className="text-base">Top-selling products</CardTitle>
                <CardDescription>By units sold in the selected period</CardDescription>
            </CardHeader>
            <CardContent>
                {topProducts.length === 0 ? (
                    <p className="text-sm text-muted-foreground">No product sales in this period.</p>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-border/60 text-left text-muted-foreground">
                                    <th className="pb-3 font-medium">Product</th>
                                    <th className="pb-3 text-right font-medium">Units</th>
                                    <th className="pb-3 text-right font-medium">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                {topProducts.map((product) => (
                                    <tr key={product.externalId} className="border-b border-border/40 last:border-0">
                                        <td className="py-3 font-medium">{product.title}</td>
                                        <td className="py-3 text-right">{product.unitsSold}</td>
                                        <td className="py-3 text-right">
                                            {formatMoney(product.revenueMinor, product.currency)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

function RecentOrdersTable() {
    const { recentOrders, filters } = usePage<PageProps>().props;

    if (!recentOrders || !filters) {
        return null;
    }

    const openOrderChat = (orderNumber: string | null) => {
        router.get('/chat', {
            store_id: filters.store_id,
            prompt: `Show order ${orderNumber ?? 'details'} details`,
        });
    };

    return (
        <Card className={dashboardCardClass}>
            <CardHeader>
                <CardTitle className="text-base">Recent orders</CardTitle>
                <CardDescription>Latest orders from your store mirror</CardDescription>
            </CardHeader>
            <CardContent>
                {recentOrders.length === 0 ? (
                    <p className="text-sm text-muted-foreground">No orders synced yet.</p>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-border/60 text-left text-muted-foreground">
                                    <th className="pb-3 font-medium">Order</th>
                                    <th className="pb-3 font-medium">Date</th>
                                    <th className="pb-3 text-right font-medium">Total</th>
                                    <th className="pb-3 font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {recentOrders.map((order) => (
                                    <tr
                                        key={order.externalId}
                                        className="cursor-pointer border-b border-border/40 last:border-0 hover:bg-muted/40"
                                        onClick={() => openOrderChat(order.orderNumber)}
                                    >
                                        <td className="py-3 font-medium">
                                            {order.orderNumber ?? order.externalId}
                                        </td>
                                        <td className="py-3">{formatDateTime(order.placedAt)}</td>
                                        <td className="py-3 text-right">
                                            {formatMoney(order.totalPriceMinor, order.currency)}
                                        </td>
                                        <td className="py-3">
                                            <Badge variant="secondary" className="rounded-lg capitalize">
                                                {(order.fulfillmentStatus ?? 'unknown').replace('_', ' ')}
                                            </Badge>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

function DashboardFilters({
    stores,
    filters,
    onChange,
}: {
    stores: StoreItem[];
    filters: Filters;
    onChange: (next: Partial<Filters>) => void;
}) {
    return (
        <div className="flex flex-wrap items-center gap-3">
            <Select
                value={filters.store_id}
                onValueChange={(storeId) => onChange({ store_id: storeId })}
            >
                <SelectTrigger className="w-[220px] rounded-xl">
                    <SelectValue placeholder="Select store" />
                </SelectTrigger>
                <SelectContent>
                    {stores.map((store) => (
                        <SelectItem key={store.id} value={store.id}>
                            {store.name}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>

            <Select value={filters.range} onValueChange={(range) => onChange({ range })}>
                <SelectTrigger className="w-[180px] rounded-xl">
                    <SelectValue placeholder="Date range" />
                </SelectTrigger>
                <SelectContent>
                    {RANGE_OPTIONS.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}

export default function DashboardPage() {
    const { hasConnectedStores } = usePage().props;
    const { stores, filters, lastSyncedAt, selectedStoreStatus } = usePage<PageProps>().props;
    const [isPending, startTransition] = useTransition();

    if (!hasConnectedStores) {
        return (
            <>
                <Head title="Dashboard" />
                <StoreSetupEmptyState />
            </>
        );
    }

    if (!filters) {
        return null;
    }

    const applyFilters = (next: Partial<Filters>) => {
        startTransition(() => {
            router.get(
                dashboard.url(),
                { ...filters, ...next },
                {
                    preserveState: true,
                    only: ['metrics', 'topProducts', 'recentOrders', 'filters', 'lastSyncedAt', 'selectedStoreStatus'],
                },
            );
        });
    };

    return (
        <>
            <Head title="Dashboard" />

            <div className={isPending ? 'opacity-60 transition-opacity duration-150 ease-out' : undefined}>
                <div className="flex flex-col gap-6 p-4 md:p-6">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <p className="max-w-md text-sm text-muted-foreground">
                            Store performance from your synced mirror.
                        </p>
                        <DashboardFilters stores={stores} filters={filters} onChange={applyFilters} />
                    </div>

                    {lastSyncedAt && (
                        <p className="text-xs text-muted-foreground">
                            Data last synced: {formatDateTime(lastSyncedAt)}
                        </p>
                    )}

                    {isStale(lastSyncedAt) && (
                        <div className="flex items-center gap-2 rounded-xl border border-amber-500/20 bg-amber-500/10 px-4 py-3 text-sm text-amber-800 dark:text-amber-200">
                            <AlertTriangle className="size-4 shrink-0" />
                            Data may be outdated.{' '}
                            <Link href="/chat" className="font-medium underline underline-offset-2">
                                Sync now
                            </Link>{' '}
                            in chat sidebar.
                        </div>
                    )}

                    {selectedStoreStatus === 'error' && (
                        <div className="flex items-center gap-2 rounded-xl border border-destructive/20 bg-destructive/10 px-4 py-3 text-sm text-destructive">
                            <AlertTriangle className="size-4 shrink-0" />
                            Store connection error — check your store settings to reconnect.
                        </div>
                    )}

                    <Deferred data="metrics" fallback={<KpiSkeletonGrid />}>
                        <KpiGrid />
                    </Deferred>

                    <div className="grid gap-6 lg:grid-cols-2">
                        <Deferred data="topProducts" fallback={<TableSkeleton />}>
                            <TopProductsTable />
                        </Deferred>
                        <Deferred data="recentOrders" fallback={<TableSkeleton />}>
                            <RecentOrdersTable />
                        </Deferred>
                    </div>
                </div>
            </div>
        </>
    );
}

DashboardPage.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
