import { Head } from '@inertiajs/react';

export default function StoresIndex() {
    return (
        <>
            <Head title="Stores" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <p className="text-muted-foreground">Connected stores — coming in Phase 5.</p>
            </div>
        </>
    );
}
