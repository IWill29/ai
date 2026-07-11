import { Head } from '@inertiajs/react';
import { billing } from '@/routes/settings';

export default function BillingSettings() {
    return (
        <>
            <Head title="Billing" />

            <div className="mx-auto flex w-full max-w-2xl flex-col gap-4 p-4 md:p-6">
                <p className="text-sm text-muted-foreground">
                    Subscription billing — coming in Phase 11.
                </p>
            </div>
        </>
    );
}

BillingSettings.layout = {
    breadcrumbs: [
        {
            title: 'Billing',
            href: billing(),
        },
    ],
};
