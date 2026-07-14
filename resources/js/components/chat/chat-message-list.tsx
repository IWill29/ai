import { MessageSquare } from 'lucide-react';
import { useEffect, useRef } from 'react';
import ChatMessageBubble from '@/components/chat/chat-message-bubble';
import { cn } from '@/lib/utils';

type Props = Readonly<{
    messages: App.Domains.Chat.DTOs.MessageDTO[];
    isStreaming?: boolean;
    className?: string;
}>;

export default function ChatMessageList({ messages, isStreaming = false, className }: Props) {
    const bottomRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'instant' });
    }, [messages, isStreaming]);

    if (messages.length === 0) {
        return (
            <div
                className={cn(
                    'flex flex-1 flex-col items-center justify-center gap-3 px-6 py-12 text-center',
                    className,
                )}
            >
                <div className="flex size-12 items-center justify-center rounded-xl bg-indigo-500/10">
                    <MessageSquare className="size-5 text-indigo-600 dark:text-indigo-400" aria-hidden />
                </div>
                <div className="max-w-md space-y-1">
                    <h2 className="text-lg font-semibold tracking-tight">Ask about your store</h2>
                    <p className="text-sm text-muted-foreground">
                        Try questions about orders, products, inventory, or customers. The agent reads
                        from your synced mirror.
                    </p>
                </div>
            </div>
        );
    }

    return (
        <div className={cn('min-h-0 flex-1 overflow-y-auto px-4 py-4 md:px-6', className)}>
            <div className="mx-auto flex max-w-4xl flex-col gap-6">
                {messages.map((message) => (
                    <ChatMessageBubble
                        key={message.id}
                        message={message}
                        isStreaming={isStreaming && message.id === 'streaming'}
                    />
                ))}
                <div ref={bottomRef} />
            </div>
        </div>
    );
}
