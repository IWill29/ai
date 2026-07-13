import { useCallback, useEffect, useRef, useState } from 'react';

function findClosestStepIndex(elements: HTMLElement[]): number {
    const viewportCenter = window.innerHeight / 2;
    let closestIndex = 0;
    let closestDistance = Number.POSITIVE_INFINITY;

    elements.forEach((element, index) => {
        const rect = element.getBoundingClientRect();

        if (rect.bottom < 0 || rect.top > window.innerHeight) {
            return;
        }

        const elementCenter = rect.top + rect.height / 2;
        const distance = Math.abs(elementCenter - viewportCenter);

        if (distance < closestDistance) {
            closestDistance = distance;
            closestIndex = index;
        }
    });

    return closestIndex;
}

export function useScrollSpySteps(itemCount: number): {
    activeIndex: number;
    setActiveIndex: (index: number) => void;
    setStepRef: (index: number) => (element: HTMLElement | null) => void;
} {
    const [activeIndex, setActiveIndex] = useState(0);
    const itemRefs = useRef<(HTMLElement | null)[]>([]);
    const rafId = useRef<number | null>(null);

    const setStepRef = useCallback(
        (index: number) => (element: HTMLElement | null) => {
            itemRefs.current[index] = element;
        },
        [],
    );

    useEffect(() => {
        const elements = itemRefs.current.filter((element): element is HTMLElement => element !== null);

        if (elements.length === 0) {
            return;
        }

        const updateActiveIndex = () => {
            rafId.current = null;
            const nextIndex = findClosestStepIndex(elements);

            setActiveIndex((current) => (current === nextIndex ? current : nextIndex));
        };

        const scheduleUpdate = () => {
            if (rafId.current !== null) {
                return;
            }

            rafId.current = window.requestAnimationFrame(updateActiveIndex);
        };

        scheduleUpdate();
        window.addEventListener('scroll', scheduleUpdate, { passive: true });
        window.addEventListener('resize', scheduleUpdate, { passive: true });

        return () => {
            window.removeEventListener('scroll', scheduleUpdate);
            window.removeEventListener('resize', scheduleUpdate);

            if (rafId.current !== null) {
                window.cancelAnimationFrame(rafId.current);
            }
        };
    }, [itemCount]);

    return { activeIndex, setActiveIndex, setStepRef };
}
