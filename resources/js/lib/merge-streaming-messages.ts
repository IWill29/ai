import type { LiveStep } from '@/types/chat';

type MessageDTO = App.Domains.Chat.DTOs.MessageDTO;
type ActionStepDTO = App.Domains.Chat.DTOs.ActionStepDTO;

function liveStepsToDto(steps: LiveStep[]): ActionStepDTO[] {
    return steps.map((step) => ({
        id: `live-${step.stepOrder}`,
        stepOrder: step.stepOrder,
        toolName: step.tool,
        arguments: step.arguments,
        targetPlatform: null,
        status: step.status,
        isWrite: step.isWrite,
        confirmed: null,
        resultSummary: step.summary ?? null,
        durationMs: step.durationMs ?? null,
    }));
}

type MergeInput = {
    messages: MessageDTO[];
    streamingText: string;
    steps: LiveStep[];
    status: string;
};

export function mergeStreamingMessages({
    messages,
    streamingText,
    steps,
    status,
}: MergeInput): MessageDTO[] {
    const isActive =
        status === 'streaming' ||
        status === 'awaiting_confirmation' ||
        streamingText.length > 0 ||
        steps.length > 0;

    if (!isActive) {
        return messages;
    }

    const liveMessage: MessageDTO = {
        id: 'streaming',
        role: 'assistant',
        content: streamingText.length > 0 ? streamingText : null,
        model: null,
        attachments: [],
        actionSteps: liveStepsToDto(steps),
    };

    return [...messages, liveMessage];
}

export function findPendingConfirmationStep(
    messages: MessageDTO[],
): App.Domains.Chat.DTOs.ActionStepDTO | null {
    for (const message of [...messages].reverse()) {
        for (const step of [...message.actionSteps].reverse()) {
            if (step.status === 'awaiting_confirmation') {
                return step;
            }
        }
    }

    return null;
}
