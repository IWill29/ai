import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import { AlertTriangle, Plus, RefreshCw, Trash2 } from 'lucide-react';
import { useState } from 'react';
import StoreController from '@/actions/App/Http/Controllers/Stores/StoreController';
import Heading from '@/components/heading';
import StoreSetupEmptyState from '@/components/stores/store-setup-empty-state';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { connect, index } from '@/routes/stores';

type StoreItem = {
    id: string;
    name: string;
    domain: string;
    platform: string;
    status: string;
    lastSyncedAt: string | null;
};

type Props = {
    stores: StoreItem[];
};

function statusVariant(status: string): 'default' | 'secondary' | 'destructive' {
    switch (status) {
        case 'active':
            return 'default';
        case 'error':
            return 'destructive';
        default:
            return 'secondary';
    }
}

function ReconnectDialog({ store }: { store: StoreItem }) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" size="sm" className="rounded-xl">
                    <RefreshCw className="mr-2 size-4" />
                    Reconnect
                </Button>
            </DialogTrigger>
            <DialogContent className="rounded-2xl sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Reconnect {store.name}</DialogTitle>
                    <DialogDescription>
                        Enter a new Admin API token for {store.domain}. We validate before saving.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...StoreController.reconnect.form(store.id)}
                    options={{ preserveScroll: true }}
                    resetOnSuccess={['access_token']}
                    onSuccess={() => setOpen(false)}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor={`token-${store.id}`}>Admin API access token</Label>
                                <PasswordInput
                                    id={`token-${store.id}`}
                                    name="access_token"
                                    placeholder="shpat_..."
                                    autoComplete="off"
                                />
                                <InputError message={errors.access_token} />
                            </div>
                            <DialogFooter>
                                <Button type="submit" disabled={processing} variant="brand">
                                    {processing ? 'Validating…' : 'Save token'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export default function StoresIndex({ stores }: Props) {
    const { hasConnectedStores } = usePage().props;
    const showEmptyState = !hasConnectedStores && stores.length === 0;

    const handleDisconnect = (store: StoreItem) => {
        if (
            !window.confirm(
                `Disconnect ${store.name}? This permanently removes credentials and synced data.`,
            )
        ) {
            return;
        }

        router.delete(StoreController.destroy.url(store.id), {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Stores" />

            {showEmptyState ? (
                <StoreSetupEmptyState />
            ) : (
                <div className="mx-auto flex w-full max-w-4xl flex-col gap-8 p-4 md:p-6">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <Heading
                            variant="small"
                            title="Connected stores"
                            description="Manage Shopify connections for your workspace."
                        />
                        <Button asChild variant="brand">
                            <Link href={connect()}>
                                <Plus className="mr-2 size-4" />
                                Connect store
                            </Link>
                        </Button>
                    </div>

                    <div className="grid gap-4">
                        {stores.map((store) => (
                            <Card
                                key={store.id}
                                className="rounded-2xl border-border/60 shadow-[0_4px_20px_-2px_rgb(0_0_0/0.08)]"
                            >
                                <CardHeader className="flex flex-row items-start justify-between gap-4 space-y-0">
                                    <div>
                                        <CardTitle className="text-lg">{store.name}</CardTitle>
                                        <CardDescription className="mt-1 font-mono text-xs">
                                            {store.domain}
                                        </CardDescription>
                                    </div>
                                    <Badge variant={statusVariant(store.status)} className="rounded-lg capitalize">
                                        {store.status}
                                    </Badge>
                                </CardHeader>
                                <CardContent className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div className="space-y-1 text-sm text-muted-foreground">
                                        <p className="capitalize">Platform: {store.platform}</p>
                                        <p>
                                            Last synced:{' '}
                                            {store.lastSyncedAt
                                                ? new Date(store.lastSyncedAt).toLocaleString()
                                                : 'Not yet synced'}
                                        </p>
                                        {store.status === 'error' && (
                                            <p className="flex items-center gap-1 text-amber-600 dark:text-amber-400">
                                                <AlertTriangle className="size-4" />
                                                Reconnect with a valid token to resume.
                                            </p>
                                        )}
                                    </div>
                                    <div className="flex flex-wrap gap-2">
                                        <ReconnectDialog store={store} />
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            className="rounded-xl text-destructive hover:text-destructive"
                                            onClick={() => handleDisconnect(store)}
                                        >
                                            <Trash2 className="mr-2 size-4" />
                                            Disconnect
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </div>
            )}
        </>
    );
}

StoresIndex.layout = {
    breadcrumbs: [{ title: 'Stores', href: index() }],
};
