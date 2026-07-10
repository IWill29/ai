import type { Auth } from '@/types/auth';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }

    // Inertia Head deduplication — not a DOM attribute but valid on Head children.
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface MetaHTMLAttributes<T> {
        'head-key'?: string;
    }

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface LinkHTMLAttributes<T> {
        'head-key'?: string;
    }

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface ScriptHTMLAttributes<T> {
        'head-key'?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            hasConnectedStores: boolean;
            [key: string]: unknown;
        };
    }
}
