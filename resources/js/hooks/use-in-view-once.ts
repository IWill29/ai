import { useEffect, useRef, useState  } from 'react';
import type {RefObject} from 'react';

export function useInViewOnce<T extends Element = HTMLDivElement>(
    threshold = 0.12,
    rootMargin = '0px',
): {
    ref: RefObject<T | null>;
    inView: boolean;
} {
    const ref = useRef<T | null>(null);
    const [inView, setInView] = useState(false);

    useEffect(() => {
        const element = ref.current;

        if (!element || inView) {
            return;
        }

        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting) {
                    setInView(true);
                    observer.disconnect();
                }
            },
            { threshold, rootMargin },
        );

        observer.observe(element);

        return () => observer.disconnect();
    }, [inView, rootMargin, threshold]);

    return { ref, inView };
}
