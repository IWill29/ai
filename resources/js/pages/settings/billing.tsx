import { Head } from '@inertiajs/react';

export default function BillingSettings() {
    return (
        <>
            <Head title="Billing" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <p className="text-muted-foreground">Subscription billing — coming in Phase 11.</p>
            </div>
        </>
    );
}
