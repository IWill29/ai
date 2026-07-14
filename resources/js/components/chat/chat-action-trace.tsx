import { ChevronDown } from 'lucide-react';
import { useState } from 'react';
import { ChatActionStepCard } from '@/components/chat/chat-action-step-card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { cn } from '@/lib/utils';

type Props = Readonly<{
    steps: App.Domains.Chat.DTOs.ActionStepDTO[];
    defaultOpen?: boolean;
}>;

export default function ChatActionTrace({ steps, defaultOpen = true }: Props) {
    const [open, setOpen] = useState(defaultOpen);

    if (steps.length === 0) {
        return null;
    }

    return (
        <Collapsible open={open} onOpenChange={setOpen} className="mt-2 ml-1 max-w-3xl">
            <CollapsibleTrigger className="flex items-center gap-1 text-xs text-muted-foreground transition-colors duration-150 hover:text-foreground">
                <ChevronDown
                    className={cn('size-3.5 transition-transform duration-150', open && 'rotate-180')}
                    aria-hidden
                />
                {steps.length} step{steps.length !== 1 ? 's' : ''}
            </CollapsibleTrigger>
            <CollapsibleContent className="mt-2 space-y-2">
                {steps.map((step) => (
                    <ChatActionStepCard key={step.id} step={step} />
                ))}
            </CollapsibleContent>
        </Collapsible>
    );
}
