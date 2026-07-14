import { useEffect } from 'react';

type Options = {
    onSend: () => void;
    disabled?: boolean;
};

export function useChatKeyboard({ onSend, disabled = false }: Options): void {
    useEffect(() => {
        const handleKeyDown = (event: KeyboardEvent) => {
            if (disabled) {
                return;
            }

            if (event.key !== 'Enter' || event.shiftKey) {
                return;
            }

            const target = event.target;

            if (!(target instanceof HTMLTextAreaElement)) {
                return;
            }

            event.preventDefault();
            onSend();
        };

        window.addEventListener('keydown', handleKeyDown);

        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [disabled, onSend]);
}
