import { ArrowUp, Square } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import ChatAttachmentPicker, {
    ChatAttachmentPreview,
    handleAttachmentDrop,
} from '@/components/chat/chat-attachment-picker';
import ChatModelPicker from '@/components/chat/chat-model-picker';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { useChatKeyboard } from '@/hooks/use-chat-keyboard';
import type { ModelTier, PendingAttachment } from '@/types/chat';

type Props = Readonly<{
    model: string;
    modelTiers: ModelTier[];
    onModelChange: (model: string) => void;
    onSend: (text: string, model: string, attachmentIds: string[]) => void;
    onStop: () => void;
    disabled?: boolean;
    isStreaming?: boolean;
    prefillPrompt?: string | null;
}>;

export default function ChatCommandInput({
    model,
    modelTiers,
    onModelChange,
    onSend,
    onStop,
    disabled = false,
    isStreaming = false,
    prefillPrompt,
}: Props) {
    const [text, setText] = useState('');
    const [attachments, setAttachments] = useState<PendingAttachment[]>([]);

    useEffect(() => {
        if (prefillPrompt) {
            setText(prefillPrompt);
        }
    }, [prefillPrompt]);

    const submit = useCallback(() => {
        if (disabled || isStreaming) {
            return;
        }

        if (!text.trim() && attachments.length === 0) {
            return;
        }

        onSend(text.trim(), model, attachments.map((item) => item.id));
        setText('');
        setAttachments([]);
    }, [attachments, disabled, isStreaming, model, onSend, text]);

    useChatKeyboard({
        onSend: submit,
        disabled: disabled || isStreaming,
    });

    const removeAttachment = (id: string) => {
        setAttachments((current) => current.filter((item) => item.id !== id));
    };

    return (
        <div className="shrink-0 border-t border-border/60 bg-background/80 p-4 backdrop-blur-sm">
            <div
                className="mx-auto max-w-3xl rounded-2xl border border-border/80 bg-card shadow-sm"
                onDragOver={(event) => event.preventDefault()}
                onDrop={(event) => {
                    event.preventDefault();

                    if (disabled || isStreaming) {
                        return;
                    }

                    void handleAttachmentDrop(event.dataTransfer.files, attachments, (attachment) => {
                        setAttachments((current) => [...current, attachment]);
                    });
                }}
            >
                <ChatAttachmentPreview items={attachments} onRemove={removeAttachment} />
                <Textarea
                    value={text}
                    onChange={(event) => setText(event.target.value)}
                    placeholder="Ask or command…"
                    rows={1}
                    disabled={disabled}
                    className="min-h-[52px] resize-none border-0 bg-transparent focus-visible:ring-0"
                />
                <div className="flex items-center justify-between px-3 pb-3">
                    <div className="flex items-center gap-1">
                        <ChatAttachmentPicker
                            attachments={attachments}
                            onAdd={(attachment) =>
                                setAttachments((current) => [...current, attachment])
                            }
                            disabled={disabled || isStreaming}
                        />
                        <ChatModelPicker
                            tiers={modelTiers}
                            value={model}
                            onChange={onModelChange}
                        />
                    </div>

                    {isStreaming ? (
                        <Button
                            type="button"
                            size="icon"
                            variant="outline"
                            onClick={onStop}
                            aria-label="Stop generating"
                            className="size-9 rounded-xl active:scale-[0.97] motion-reduce:active:scale-100 transition-transform duration-150"
                            style={{ transitionTimingFunction: 'var(--ease-out-strong)' }}
                        >
                            <Square className="size-4 fill-current" aria-hidden />
                        </Button>
                    ) : (
                        <Button
                            type="button"
                            size="icon"
                            onClick={submit}
                            disabled={disabled || (!text.trim() && attachments.length === 0)}
                            aria-label="Send message"
                            className="size-9 rounded-xl active:scale-[0.97] motion-reduce:active:scale-100 transition-transform duration-150"
                            style={{ transitionTimingFunction: 'var(--ease-out-strong)' }}
                        >
                            <ArrowUp className="size-4" aria-hidden />
                        </Button>
                    )}
                </div>
            </div>
        </div>
    );
}
