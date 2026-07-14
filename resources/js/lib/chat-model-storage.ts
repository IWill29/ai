const STORAGE_KEY = 'agentstore.chat.model';

export function loadLastModel(fallback: string): string {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);

        return stored && stored.length > 0 ? stored : fallback;
    } catch {
        return fallback;
    }
}

export function saveLastModel(model: string): void {
    try {
        localStorage.setItem(STORAGE_KEY, model);
    } catch {
        // ignore quota / private mode
    }
}

export function shortModelName(model: string): string {
    const parts = model.split('/');

    return parts.at(-1) ?? model;
}
