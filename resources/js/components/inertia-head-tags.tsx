import { createElement  } from 'react';
import type {ComponentPropsWithoutRef} from 'react';

/** Inertia Head deduplication attribute — not a standard DOM property. */
const HEAD_KEY = 'head-key';

type HeadKeyProps = Readonly<{
    headKey: string;
}>;

export function HeadMeta({ headKey, ...props }: ComponentPropsWithoutRef<'meta'> & HeadKeyProps) {
    return createElement('meta', { ...props, [HEAD_KEY]: headKey });
}

export function HeadLink({ headKey, ...props }: ComponentPropsWithoutRef<'link'> & HeadKeyProps) {
    return createElement('link', { ...props, [HEAD_KEY]: headKey });
}

export function HeadScript({
    headKey,
    ...props
}: ComponentPropsWithoutRef<'script'> & HeadKeyProps) {
    return createElement('script', { ...props, [HEAD_KEY]: headKey });
}
