import { Settings2 } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { loadLastModel, saveLastModel, shortModelName } from '@/lib/chat-model-storage';
import type { ModelTier } from '@/types/chat';

type Props = Readonly<{
    tiers: ModelTier[];
    value: string;
    onChange: (model: string) => void;
}>;

export default function ChatModelPicker({ tiers, value, onChange }: Props) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger
                className="flex items-center gap-1 rounded-lg px-2 py-1 text-xs text-muted-foreground transition-colors duration-150 hover:bg-muted data-[state=open]:bg-muted"
                style={{ transformOrigin: 'bottom left' }}
            >
                <Settings2 className="size-3.5" aria-hidden />
                {shortModelName(value)}
            </DropdownMenuTrigger>
            <DropdownMenuContent
                align="start"
                className="rounded-xl motion-safe:animate-in motion-safe:fade-in motion-safe:zoom-in-95 motion-safe:duration-200"
                style={{ animationTimingFunction: 'var(--ease-out-strong)' }}
            >
                {tiers.map((tier) => (
                    <DropdownMenuGroup key={tier.tier}>
                        <DropdownMenuLabel>{tier.tier}</DropdownMenuLabel>
                        {tier.models.map((model) => (
                            <DropdownMenuItem
                                key={model}
                                onClick={() => {
                                    onChange(model);
                                    saveLastModel(model);
                                }}
                            >
                                {model}
                            </DropdownMenuItem>
                        ))}
                    </DropdownMenuGroup>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

export function getInitialModel(defaultModel: string | null, tiers: ModelTier[]): string {
    const fallback =
        defaultModel ??
        tiers.flatMap((tier) => tier.models)[0] ??
        'openai/gpt-4o-mini';

    return loadLastModel(fallback);
}
