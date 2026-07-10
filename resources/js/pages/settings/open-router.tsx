import { Head } from '@inertiajs/react';
import AiKeysSetupPanel from '@/components/settings/ai-keys-setup-panel';
import { openrouter } from '@/routes/settings';

type OpenRouterState = {
    configured: boolean;
    maskedKey: string | null;
    validatedAt: string | null;
    defaultModel: string | null;
};

type Props = {
    openRouter: OpenRouterState;
    suggestedModels: string[];
};

export default function OpenRouterSettings({ openRouter, suggestedModels }: Props) {
    return (
        <>
            <Head title="AI keys" />
            <AiKeysSetupPanel openRouter={openRouter} suggestedModels={suggestedModels} />
        </>
    );
}

OpenRouterSettings.layout = {
    breadcrumbs: [
        {
            title: 'AI keys',
            href: openrouter(),
        },
    ],
};
