export function formatDateTime(value: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

export function formatRelative(value: string): string {
    const date = new Date(value);
    const diffMs = date.getTime() - Date.now();
    const diffMinutes = Math.round(diffMs / 60_000);

    const formatter = new Intl.RelativeTimeFormat(undefined, { numeric: 'auto' });

    if (Math.abs(diffMinutes) < 60) {
        return formatter.format(diffMinutes, 'minute');
    }

    const diffHours = Math.round(diffMinutes / 60);

    if (Math.abs(diffHours) < 24) {
        return formatter.format(diffHours, 'hour');
    }

    const diffDays = Math.round(diffHours / 24);

    if (Math.abs(diffDays) < 7) {
        return formatter.format(diffDays, 'day');
    }

    return formatDateTime(value);
}
