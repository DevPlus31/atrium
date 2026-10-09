import type { SVGAttributes } from 'react';

/**
 * The Atrium mark: three arched openings on a shared plinth — the central
 * hall flanked by its colonnade. Drawn in `currentColor`.
 *
 * @branding Replace the paths with your product's mark (keep `currentColor`
 * so it follows the theme). It appears in the sidebar/topbar logo, the auth
 * pages and the error page; also update public/favicon.svg, favicon.ico and
 * apple-touch-icon.png to match.
 */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
            <path d="M15 34V11a5 5 0 0 1 10 0v23z" />
            <path d="M4 34V18.5a4.5 4.5 0 0 1 9 0V34z" opacity="0.7" />
            <path d="M27 34V18.5a4.5 4.5 0 0 1 9 0V34z" opacity="0.7" />
            <rect x="2" y="35" width="36" height="3" rx="1" />
        </svg>
    );
}
