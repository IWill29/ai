import { useCallback, useEffect, useRef, useState } from 'react';

export function useScrollSpySteps(itemCount: number): {
    activeIndex: number;
    setActiveIndex: (index: number) => void;
    setStepRef: (index: number) => (element: HTMLElement | null) => void;
} {
    const [activeIndex, setActiveIndex] = useState(0);
    const itemRefs = useRef<(HTMLElement | null)[]>([]);

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

        const observer = new IntersectionObserver(
            (entries) => {
                const visible = entries.filter((entry) => entry.isIntersecting);

                if (visible.length === 0) {
                    return;
                }

                const best = visible.reduce((current, entry) =>
                    entry.intersectionRatio > current.intersectionRatio ? entry : current,
                );

                const index = elements.indexOf(best.target as HTMLElement);

                if (index >= 0) {
                    setActiveIndex(index);
                }
            },
            {
                rootMargin: '-35% 0px -35% 0px',
                threshold: [0, 0.2, 0.45, 0.7, 1],
            },
        );

        elements.forEach((element) => observer.observe(element));

        return () => observer.disconnect();
    }, [itemCount]);

    return { activeIndex, setActiveIndex, setStepRef };
}
