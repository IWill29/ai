import { Head, router, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { toast } from 'sonner';
import ChatCommandInput from '@/components/chat/chat-command-input';
import ChatConfirmationBar from '@/components/chat/chat-confirmation-bar';
import ChatEmptyState from '@/components/chat/chat-empty-state';
import ChatLayout from '@/components/chat/chat-layout';
import ChatMessageList from '@/components/chat/chat-message-list';
import ChatNoByokEmptyState from '@/components/chat/chat-no-byok-empty-state';
import { getInitialModel } from '@/components/chat/chat-model-picker';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { confirmActionStep, createConversation } from '@/lib/chat-api';
import { saveLastModel } from '@/lib/chat-model-storage';
import { mergeStreamingMessages } from '@/lib/merge-streaming-messages';
import { useAgentStream } from '@/hooks/use-agent-stream';
import type { ChatPageProps } from '@/types/chat';
import { index as chatIndex } from '@/routes/chat';

export default function ChatIndex() {
    const props = usePage<ChatPageProps>().props;
    const {
        hasStores,
        hasValidByok,
        activeStoreId,
        activeConversationId,
        initialMessages = [],
        prefillPrompt,
        modelTiers,
        defaultModel,
    } = props;

    const [conversationId, setConversationId] = useState<string | null>(
        activeConversationId ?? null,
    );
    const [messages, setMessages] = useState(initialMessages);
    const [model, setModel] = useState(() => getInitialModel(defaultModel ?? null, modelTiers));
    const [confirmSubmitting, setConfirmSubmitting] = useState(false);

    const { state, send, stop, resumeAfterConfirm, reset } = useAgentStream(conversationId);

    useEffect(() => {
        setConversationId(activeConversationId ?? null);
        reset();
    }, [activeConversationId, reset]);

    useEffect(() => {
        setMessages(initialMessages);
    }, [initialMessages]);

    const displayMessages = useMemo(
        () =>
            mergeStreamingMessages({
                messages,
                streamingText: state.streamingText,
                steps: state.steps,
                status: state.status,
            }),
        [messages, state.streamingText, state.steps, state.status],
    );

    const reloadConversation = useCallback(() => {
        router.reload({
            only: ['initialMessages', 'conversations', 'activeConversationId'],
        });
    }, []);

    const ensureConversation = useCallback(
        async (selectedModel: string): Promise<string | null> => {
            if (conversationId !== null) {
                return conversationId;
            }

            if (activeStoreId === null) {
                return null;
            }

            const conversation = await createConversation(activeStoreId, selectedModel);
            setConversationId(conversation.id);
            window.history.replaceState({}, '', `/chat/${conversation.id}`);

            return conversation.id;
        },
        [activeStoreId, conversationId],
    );

    const handleSend = useCallback(
        async (text: string, selectedModel: string, attachmentIds: string[]) => {
            if (!hasValidByok || activeStoreId === null) {
                return;
            }

            saveLastModel(selectedModel);

            try {
                const id = await ensureConversation(selectedModel);

                if (id === null) {
                    return;
                }

                if (id !== conversationId) {
                    setConversationId(id);
                }

                const optimisticUser: App.Domains.Chat.DTOs.MessageDTO = {
                    id: `pending-${Date.now()}`,
                    role: 'user',
                    content: text,
                    model: null,
                    attachments: [],
                    actionSteps: [],
                };

                setMessages((current) => [...current, optimisticUser]);

                await send(text, selectedModel, attachmentIds, id);

                reloadConversation();
            } catch {
                toast.error('Could not send your message.');
            }
        },
        [
            activeStoreId,
            conversationId,
            ensureConversation,
            hasValidByok,
            reloadConversation,
            send,
        ],
    );

    const handleConfirm = useCallback(
        async (actionStepId: string, confirmed: boolean) => {
            setConfirmSubmitting(true);

            try {
                await confirmActionStep(actionStepId, confirmed);
                await resumeAfterConfirm();
                reloadConversation();
            } catch {
                toast.error('Could not process your confirmation.');
            } finally {
                setConfirmSubmitting(false);
            }
        },
        [reloadConversation, resumeAfterConfirm],
    );

    const handleNewChat = useCallback(() => {
        reset();
        router.get(chatIndex());
    }, [reset]);

    if (!hasStores) {
        return (
            <>
                <Head title="Chat" />
                <ChatEmptyState />
            </>
        );
    }

    if (!hasValidByok) {
        return (
            <>
                <Head title="Chat" />
                <ChatNoByokEmptyState />
            </>
        );
    }

    const isStreaming = state.status === 'streaming';

    return (
        <>
            <Head title="Chat" />

            <ChatLayout {...props} activeConversationId={conversationId} onNewChat={handleNewChat}>
                <ChatMessageList messages={displayMessages} isStreaming={isStreaming} />

                {state.warning && (
                    <Alert variant="default" className="mx-auto mb-2 max-w-3xl rounded-xl">
                        <AlertDescription>{state.warning}</AlertDescription>
                    </Alert>
                )}

                {state.pendingConfirmation && (
                    <ChatConfirmationBar
                        payload={state.pendingConfirmation}
                        isSubmitting={confirmSubmitting}
                        onConfirm={(id) => void handleConfirm(id, true)}
                        onCancel={(id) => void handleConfirm(id, false)}
                    />
                )}

                <ChatCommandInput
                    model={model}
                    modelTiers={modelTiers}
                    onModelChange={setModel}
                    onSend={(text, selectedModel, attachmentIds) =>
                        void handleSend(text, selectedModel, attachmentIds)
                    }
                    onStop={stop}
                    disabled={state.status === 'awaiting_confirmation'}
                    isStreaming={isStreaming}
                    prefillPrompt={prefillPrompt}
                />
            </ChatLayout>
        </>
    );
}

ChatIndex.layout = {
    breadcrumbs: [
        {
            title: 'Chat',
            href: chatIndex(),
        },
    ],
};
