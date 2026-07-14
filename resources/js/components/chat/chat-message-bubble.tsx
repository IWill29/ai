import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import ChatActionTrace from '@/components/chat/chat-action-trace';
import { cn } from '@/lib/utils';

type MessageDTO = App.Domains.Chat.DTOs.MessageDTO;

function AssistantContent({ content }: { content: string }) {
    return (
        <div className="prose prose-neutral dark:prose-invert max-w-none text-[15px] prose-p:my-2 prose-table:text-sm prose-th:px-2 prose-td:px-2">
            <ReactMarkdown remarkPlugins={[remarkGfm]}>{content}</ReactMarkdown>
        </div>
    );
}

type Props = Readonly<{
    message: MessageDTO;
    isStreaming?: boolean;
}>;

export default function ChatMessageBubble({ message, isStreaming = false }: Props) {
    const isUser = message.role === 'user';

    return (
        <div className={cn('flex w-full', isUser ? 'justify-end' : 'justify-start')}>
            <div className={cn('max-w-3xl space-y-1', isUser ? 'items-end' : 'items-start')}>
                <div
                    className={cn(
                        'rounded-2xl px-4 py-3 text-sm leading-relaxed shadow-sm',
                        isUser
                            ? 'rounded-tr-md bg-indigo-500/12 text-foreground ring-1 ring-indigo-500/20'
                            : 'rounded-tl-md border border-border/60 bg-card/90',
                    )}
                >
                    {isUser ? (
                        <div className="space-y-2">
                            {message.attachments.length > 0 && (
                                <div className="flex flex-wrap gap-2">
                                    {message.attachments.map((attachment) => (
                                        <img
                                            key={attachment.id}
                                            src={attachment.previewUrl}
                                            alt={attachment.filename}
                                            className="h-14 w-14 rounded-lg border border-border/60 object-cover"
                                        />
                                    ))}
                                </div>
                            )}
                            <p className="whitespace-pre-wrap">{message.content}</p>
                        </div>
                    ) : message.content ? (
                        <AssistantContent content={message.content} />
                    ) : isStreaming ? (
                        <p className="text-muted-foreground">Thinking…</p>
                    ) : null}
                </div>

                {!isUser && message.actionSteps.length > 0 && (
                    <ChatActionTrace steps={message.actionSteps} />
                )}
            </div>
        </div>
    );
}
