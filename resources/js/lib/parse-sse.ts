export type ParsedSseEvent = {
    event: string;
    data: Record<string, unknown>;
};

export function parseSseChunk(buffer: string): { events: ParsedSseEvent[]; remainder: string } {
    const events: ParsedSseEvent[] = [];
    const parts = buffer.split('\n\n');
    const remainder = parts.pop() ?? '';

    for (const part of parts) {
        if (part.trim() === '') {
            continue;
        }

        let eventName = 'message';
        let dataLine = '';

        for (const line of part.split('\n')) {
            if (line.startsWith('event:')) {
                eventName = line.slice(6).trim();
            } else if (line.startsWith('data:')) {
                dataLine = line.slice(5).trim();
            }
        }

        if (dataLine === '') {
            continue;
        }

        try {
            const data = JSON.parse(dataLine) as Record<string, unknown>;
            events.push({ event: eventName, data });
        } catch {
            // ignore malformed chunks
        }
    }

    return { events, remainder };
}

export async function consumeSseResponse(
    response: Response,
    onEvent: (event: ParsedSseEvent) => void,
    signal?: AbortSignal,
): Promise<void> {
    const reader = response.body?.getReader();

    if (!reader) {
        throw new Error('Streaming is not supported in this browser.');
    }

    const decoder = new TextDecoder();
    let buffer = '';

    while (true) {
        if (signal?.aborted) {
            await reader.cancel();
            break;
        }

        const { done, value } = await reader.read();

        if (done) {
            break;
        }

        buffer += decoder.decode(value, { stream: true });
        const parsed = parseSseChunk(buffer);
        buffer = parsed.remainder;

        for (const event of parsed.events) {
            onEvent(event);
        }
    }

    if (buffer.trim() !== '') {
        const parsed = parseSseChunk(`${buffer}\n\n`);

        for (const event of parsed.events) {
            onEvent(event);
        }
    }
}
