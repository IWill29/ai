import { useCallback, useRef, useState } from 'react';
import { toast } from 'sonner';
import { getCsrfToken } from '@/lib/csrf';
import { consumeSseResponse, type ParsedSseEvent } from '@/lib/parse-sse';
import type {
    AgentStreamStatus,
    ConfirmationPayload,
    LiveStep,
} from '@/types/chat';

type StreamState = {
    streamingText: string;
    steps: LiveStep[];
    pendingConfirmation: ConfirmationPayload | null;
    status: AgentStreamStatus;
    warning: string | null;
    error: string | null;
};

const initialState: StreamState = {
    streamingText: '',
    steps: [],
    pendingConfirmation: null,
    status: 'idle',
    warning: null,
    error: null,
};

function applyEvent(prev: StreamState, event: ParsedSseEvent): StreamState {
    switch (event.event) {
        case 'text_delta': {
            const content = typeof event.data.content === 'string' ? event.data.content : '';

            return {
                ...prev,
                streamingText: prev.streamingText + content,
            };
        }
        case 'step_started': {
            const stepOrder = Number(event.data.step_order ?? 0);

            return {
                ...prev,
                steps: [
                    ...prev.steps.filter((step) => step.stepOrder !== stepOrder),
                    {
                        stepOrder,
                        tool: String(event.data.tool ?? ''),
                        arguments: (event.data.arguments as Record<string, unknown>) ?? {},
                        isWrite: Boolean(event.data.is_write),
                        status: 'running',
                    },
                ],
            };
        }
        case 'step_done': {
            const stepOrder = Number(event.data.step_order ?? 0);
            const status = event.data.status === 'failed' ? 'failed' : 'done';

            return {
                ...prev,
                steps: prev.steps.map((step) =>
                    step.stepOrder === stepOrder
                        ? {
                              ...step,
                              status,
                              durationMs:
                                  typeof event.data.duration_ms === 'number'
                                      ? event.data.duration_ms
                                      : undefined,
                              summary: (event.data.summary as Record<string, unknown>) ?? null,
                          }
                        : step,
                ),
            };
        }
        case 'confirmation_required': {
            return {
                ...prev,
                pendingConfirmation: {
                    action_step_id: String(event.data.action_step_id ?? ''),
                    tool: String(event.data.tool ?? ''),
                    arguments: (event.data.arguments as Record<string, unknown>) ?? {},
                    description: String(event.data.description ?? 'Review this change'),
                    image_previews: Array.isArray(event.data.image_previews)
                        ? (event.data.image_previews as string[])
                        : undefined,
                    description_preview:
                        typeof event.data.description_preview === 'string'
                            ? event.data.description_preview
                            : null,
                },
                status: 'awaiting_confirmation',
            };
        }
        case 'memory_saved':
            toast.success('Preference saved for future chats');
            return prev;
        case 'warning':
            return {
                ...prev,
                warning: String(event.data.message ?? 'Something needs your attention.'),
            };
        case 'error':
            return {
                ...prev,
                status: 'error',
                error: String(event.data.message ?? 'The assistant could not finish this request.'),
            };
        case 'done': {
            const doneStatus = event.data.status === 'awaiting_confirmation'
                ? 'awaiting_confirmation'
                : 'done';

            return {
                ...prev,
                status: doneStatus,
            };
        }
        default:
            return prev;
    }
}

export function useAgentStream(conversationId: string | null) {
    const [state, setState] = useState<StreamState>(initialState);
    const abortRef = useRef<AbortController | null>(null);

    const runStream = useCallback(
        async (url: string, body?: Record<string, unknown>) => {
            if (conversationId === null) {
                return;
            }

            abortRef.current?.abort();
            const controller = new AbortController();
            abortRef.current = controller;

            setState({
                ...initialState,
                status: 'streaming',
            });

            const response = await fetch(url, {
                method: 'POST',
                signal: controller.signal,
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'text/event-stream',
                    'X-XSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: body ? JSON.stringify(body) : undefined,
            });

            if (!response.ok) {
                setState((current) => ({
                    ...current,
                    status: 'error',
                    error: 'Could not reach the assistant. Check your connection and try again.',
                }));

                return;
            }

            try {
                await consumeSseResponse(
                    response,
                    (event) => {
                        setState((current) => applyEvent(current, event));
                    },
                    controller.signal,
                );
            } catch (error) {
                if (controller.signal.aborted) {
                    setState((current) => ({
                        ...current,
                        status: 'done',
                    }));

                    return;
                }

                setState((current) => ({
                    ...current,
                    status: 'error',
                    error:
                        error instanceof Error
                            ? error.message
                            : 'The assistant stopped unexpectedly.',
                }));
            }
        },
        [conversationId],
    );

    const send = useCallback(
        async (
            message: string,
            model: string,
            attachmentIds: string[] = [],
            overrideConversationId?: string,
        ) => {
            const targetConversationId = overrideConversationId ?? conversationId;

            if (targetConversationId === null) {
                return;
            }

            await runStream(`/conversations/${targetConversationId}/stream`, {
                message,
                model,
                attachment_ids: attachmentIds,
            });
        },
        [conversationId, runStream],
    );

    const stop = useCallback(() => {
        abortRef.current?.abort();
        abortRef.current = null;
        setState((current) => ({
            ...current,
            status: 'done',
        }));
    }, []);

    const resumeAfterConfirm = useCallback(async () => {
        if (conversationId === null) {
            return;
        }

        setState((current) => ({
            ...current,
            pendingConfirmation: null,
            status: 'streaming',
        }));

        await runStream(`/conversations/${conversationId}/stream/resume`);
    }, [conversationId, runStream]);

    const reset = useCallback(() => {
        abortRef.current?.abort();
        abortRef.current = null;
        setState(initialState);
    }, []);

    return {
        state,
        send,
        stop,
        resumeAfterConfirm,
        reset,
    };
}
