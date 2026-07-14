import { getCsrfToken } from '@/lib/csrf';

type ConversationResponse = {
    conversation: App.Domains.Chat.DTOs.ConversationDTO;
};

type AttachmentResponse = {
    attachment: App.Domains.Chat.DTOs.AttachmentDTO;
};

export async function createConversation(
    storeConnectionId: string,
    model: string,
): Promise<App.Domains.Chat.DTOs.ConversationDTO> {
    const response = await fetch('/conversations', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-XSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            store_connection_id: storeConnectionId,
            model,
        }),
    });

    if (!response.ok) {
        throw new Error('Could not start a new chat.');
    }

    const data = (await response.json()) as ConversationResponse;

    return data.conversation;
}

export async function confirmActionStep(actionStepId: string, confirmed: boolean): Promise<void> {
    const response = await fetch(`/action-steps/${actionStepId}/confirm`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-XSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify({ confirmed }),
    });

    if (!response.ok) {
        throw new Error('Could not submit your confirmation.');
    }
}

export async function uploadAttachment(file: File): Promise<App.Domains.Chat.DTOs.AttachmentDTO> {
    const formData = new FormData();
    formData.append('file', file);

    const response = await fetch('/attachments', {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'X-XSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: formData,
    });

    if (!response.ok) {
        throw new Error('Could not upload image.');
    }

    const data = (await response.json()) as AttachmentResponse;

    return data.attachment;
}
