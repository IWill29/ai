import { router } from '@inertiajs/react';
import { useDeferredValue, useState } from 'react';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { formatRelative } from '@/lib/format-datetime';
import type { ConversationSummary } from '@/types/chat';

type Props = Readonly<{
    conversations: ConversationSummary[];
    activeConversationId?: string | null;
    onSelect?: () => void;
    className?: string;
}>;

export default function ChatHistoryPanel({
    conversations,
    activeConversationId,
    onSelect,
    className,
}: Props) {
    const [query, setQuery] = useState('');
    const deferredQuery = useDeferredValue(query);

    const filtered = conversations.filter((conversation) => {
        const title = conversation.title ?? 'New chat';

        return title.toLowerCase().includes(deferredQuery.toLowerCase());
    });

    return (
        <div className={cn('flex h-full min-h-0 flex-col', className)}>
            <div className="border-b border-border/60 p-3">
                <Input
                    placeholder="Search chats…"
                    value={query}
                    onChange={(event) => setQuery(event.target.value)}
                    className="rounded-xl"
                />
            </div>

            <div className="min-h-0 flex-1 overflow-y-auto">
                {filtered.length === 0 ? (
                    <p className="px-4 py-6 text-center text-sm text-muted-foreground">
                        {conversations.length === 0 ? 'No chats yet' : 'No matching chats'}
                    </p>
                ) : (
                    filtered.map((conversation) => {
                        const isActive = conversation.id === activeConversationId;

                        return (
                            <button
                                key={conversation.id}
                                type="button"
                                onClick={() => {
                                    router.get(`/chat/${conversation.id}`);
                                    onSelect?.();
                                }}
                                className={cn(
                                    'w-full px-4 py-3 text-left text-sm transition-colors duration-150',
                                    isActive ? 'bg-muted/70' : 'hover:bg-muted/50',
                                )}
                            >
                                <p className="truncate font-medium">
                                    {conversation.title ?? 'New chat'}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {formatRelative(conversation.updatedAt)}
                                </p>
                            </button>
                        );
                    })
                )}
            </div>
        </div>
    );
}
