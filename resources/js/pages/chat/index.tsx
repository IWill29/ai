import { Head } from '@inertiajs/react';

export default function ChatIndex() {
    return (
        <>
            <Head title="Chat" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <p className="text-muted-foreground">AI chat — coming in Phase 10.</p>
            </div>
        </>
    );
}
