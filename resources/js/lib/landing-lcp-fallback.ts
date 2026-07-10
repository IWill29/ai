import { useLayoutEffect } from 'react';

export function hideLandingLcpFallback(): void {
    document.getElementById('landing-lcp-fallback')?.classList.add('is-hidden');
    document.body.classList.remove('landing-loading');
    document.body.classList.add('landing-loaded');
}

export function useHideLandingLcpFallback(): void {
    useLayoutEffect(() => {
        const app = document.getElementById('app');

        if (!app) {
            return;
        }

        // Wait for React to paint before swapping static shell → app UI.
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                app.classList.add('inertia-ready');
                hideLandingLcpFallback();
            });
        });
    }, []);
}
