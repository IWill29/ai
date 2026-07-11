import { useId } from 'react';
import type { SVGAttributes } from 'react';
import { cn } from '@/lib/utils';

/** Geometric "A" monogram — AgentStore wordmark initial */
const A_BODY =
    'M40 19 25.5 58h7.3l3-9.8h8.4l3 9.8h7.3L40 19Zm-2.8 22.5h5.6L40 33.2l-2.8 8.3Z';

/** Agent orbit arc — live AI commerce signal */
const AGENT_ARC = 'M51.2 17.2a9.2 9.2 0 0 1 10.3 9.1';

/**
 * AgentStore mark — premium dual-theme monogram.
 * Light: indigo depth · Dark: obsidian glass tile.
 */
export default function AppLogoIcon({
    className,
    ...props
}: SVGAttributes<SVGElement>) {
    const tileLight = useId();
    const tileDark = useId();
    const shine = useId();

    return (
        <svg
            viewBox="0 0 80 80"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden
            className={cn('size-full', className)}
            {...props}
        >
            <defs>
                <linearGradient
                    id={tileLight}
                    x1="12"
                    y1="8"
                    x2="68"
                    y2="72"
                    gradientUnits="userSpaceOnUse"
                >
                    <stop stopColor="#3730A3" />
                    <stop stopColor="#6366F1" />
                </linearGradient>
                <linearGradient
                    id={tileDark}
                    x1="12"
                    y1="8"
                    x2="68"
                    y2="72"
                    gradientUnits="userSpaceOnUse"
                >
                    <stop stopColor="#1E2130" />
                    <stop stopColor="#12141F" />
                </linearGradient>
                <linearGradient
                    id={shine}
                    x1="40"
                    y1="6"
                    x2="40"
                    y2="38"
                    gradientUnits="userSpaceOnUse"
                >
                    <stop stopColor="#FFFFFF" stopOpacity="0.2" />
                    <stop stopColor="#FFFFFF" stopOpacity="0" />
                </linearGradient>
            </defs>

            <g className="dark:hidden">
                <rect width="80" height="80" rx="18" fill={`url(#${tileLight})`} />
                <rect width="80" height="38" rx="18" fill={`url(#${shine})`} />
                <path
                    d={AGENT_ARC}
                    stroke="#34D399"
                    strokeOpacity="0.45"
                    strokeWidth="1.75"
                    strokeLinecap="round"
                    fill="none"
                />
                <circle cx="60.5" cy="25.5" r="6" fill="#34D399" fillOpacity="0.2" />
                <circle cx="60.5" cy="25.5" r="3.75" fill="#34D399" />
                <circle cx="60.5" cy="25.5" r="1.35" fill="#ECFDF5" />
                <path fill="#FFFFFF" fillRule="evenodd" clipRule="evenodd" d={A_BODY} />
            </g>

            <g className="hidden dark:block">
                <rect
                    width="80"
                    height="80"
                    rx="18"
                    fill={`url(#${tileDark})`}
                    stroke="rgba(255,255,255,0.1)"
                    strokeWidth="1"
                />
                <rect width="80" height="34" rx="18" fill={`url(#${shine})`} />
                <path
                    d={AGENT_ARC}
                    stroke="#34D399"
                    strokeOpacity="0.35"
                    strokeWidth="1.75"
                    strokeLinecap="round"
                    fill="none"
                />
                <circle cx="60.5" cy="25.5" r="6" fill="#34D399" fillOpacity="0.16" />
                <circle cx="60.5" cy="25.5" r="3.5" fill="#34D399" />
                <circle cx="60.5" cy="25.5" r="1.2" fill="#D1FAE5" />
                <path fill="#E8EAF2" fillRule="evenodd" clipRule="evenodd" d={A_BODY} />
            </g>
        </svg>
    );
}
