import { useId } from 'react';
import type { SVGAttributes } from 'react';

const FANLIGHT_ANGLES = [22.5, 45, 67.5, 90, 112.5, 135, 157.5];

/** A point on the fanlight circle centred at (240, 200). */
function onArc(radius: number, degrees: number): [number, number] {
    const radians = (degrees * Math.PI) / 180;

    return [240 + radius * Math.cos(radians), 200 - radius * Math.sin(radians)];
}

/**
 * @branding Decorative artwork of the authentication pages' brand panel.
 * Swap it for your own illustration (or remove it from auth-layout.tsx);
 * drawing in `currentColor` keeps it right in every theme and appearance.
 *
 * The auth panel artwork: an atrium seen from its floor — a great arch with
 * a fanlight, a skylight beam falling through it, flanking arcades and a
 * receding floor. Everything is drawn in `currentColor` at low opacity, so
 * it re-colors with every theme preset and appearance.
 */
export function AtriumHallArt(props: SVGAttributes<SVGSVGElement>) {
    const id = useId();
    const beam = `${id}-beam`;
    const grain = `${id}-grain`;

    return (
        <svg
            aria-hidden
            viewBox="0 0 480 400"
            preserveAspectRatio="xMidYMax slice"
            fill="none"
            stroke="currentColor"
            {...props}
        >
            <defs>
                <linearGradient id={beam} x1="0" y1="0" x2="0" y2="1">
                    <stop
                        offset="0"
                        stopColor="currentColor"
                        stopOpacity="0.28"
                    />
                    <stop offset="1" stopColor="currentColor" stopOpacity="0" />
                </linearGradient>
                <filter id={grain}>
                    <feTurbulence
                        type="fractalNoise"
                        baseFrequency="0.85"
                        numOctaves="2"
                        stitchTiles="stitch"
                    />
                    <feColorMatrix type="saturate" values="0" />
                </filter>
            </defs>

            {/* Skylight beam */}
            <polygon
                points="205,36 275,36 392,400 88,400"
                fill={`url(#${beam})`}
                stroke="none"
            />

            {/* Floor, receding to the vanishing point */}
            <g strokeOpacity="0.1" strokeWidth="1">
                {[-160, -20, 120, 240, 360, 500, 640].map((x) => (
                    <line key={x} x1="240" y1="250" x2={x} y2="400" />
                ))}
                {[318, 352, 392].map((y) => (
                    <line key={y} x1="0" y1={y} x2="480" y2={y} />
                ))}
            </g>

            {/* Flanking arcades */}
            <g strokeOpacity="0.22" strokeWidth="1.25">
                <path d="M8 400V262a20 20 0 0 1 40 0v138" />
                <path d="M432 400V262a20 20 0 0 1 40 0v138" />
            </g>

            {/* The great arch: outer jamb, arch and inner reveal */}
            <path
                d="M64 400V200a176 176 0 0 1 352 0v200"
                strokeOpacity="0.16"
                strokeWidth="1.25"
            />
            <path
                d="M80 400V200a160 160 0 0 1 320 0v200"
                strokeOpacity="0.4"
                strokeWidth="1.5"
            />
            <path
                d="M120 400V200a120 120 0 0 1 240 0v200"
                strokeOpacity="0.2"
                strokeWidth="1.25"
            />

            {/* Fanlight */}
            <path
                d="M180 200a60 60 0 0 1 120 0"
                strokeOpacity="0.35"
                strokeWidth="1.25"
            />
            <g strokeOpacity="0.26" strokeWidth="1">
                {FANLIGHT_ANGLES.map((angle) => {
                    const [x1, y1] = onArc(60, angle);
                    const [x2, y2] = onArc(160, angle);

                    return <line key={angle} x1={x1} y1={y1} x2={x2} y2={y2} />;
                })}
            </g>

            {/* Dust in the light */}
            <g fill="currentColor" stroke="none" fillOpacity="0.45">
                <circle cx="228" cy="128" r="1.4" />
                <circle cx="256" cy="176" r="1.1" />
                <circle cx="221" cy="236" r="1.6" />
                <circle cx="264" cy="292" r="1.2" />
                <circle cx="243" cy="348" r="1.4" />
            </g>

            {/* Grain */}
            <rect
                width="480"
                height="400"
                filter={`url(#${grain})`}
                opacity="0.06"
                stroke="none"
            />
        </svg>
    );
}
