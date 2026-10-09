import { Link, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Blocks, Palette, ShieldCheck } from 'lucide-react';
import { useState } from 'react';
import { AnnouncementBanner } from '@/components/announcement-banner';
import { AtriumHallArt } from '@/components/auth/atrium-hall-art';
import { BrandMark } from '@/components/brand-mark';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

/**
 * @branding The product pitch on the authentication pages: the tagline (in
 * the layout below) and these three highlights. Rewrite them for your
 * product; strings are translation keys in lang/en.json.
 */
const highlights = [
    { icon: Blocks, label: 'Modules plug into one shell' },
    { icon: ShieldCheck, label: 'Permissions, never guesswork' },
    { icon: Palette, label: 'Themes and layouts for every team' },
];

/**
 * Split layout for every authentication page: the branded atrium panel on
 * the start side, the form on the other. In light mode the panel is the
 * primary color; in dark mode it is the dim sidebar surface with the artwork
 * drawn in the primary color, so no preset turns it into a glaring slab. On
 * small screens the panel shrinks to a branded band above the form.
 */
export default function AuthLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    // Per mount, not per module: a long-lived SSR process spans New Year.
    const [currentYear] = useState(() => new Date().getFullYear());
    const { t } = useLaravelReactI18n();
    const { name } = usePage().props;

    return (
        <div className="grid min-h-svh bg-background lg:grid-cols-[minmax(0,1.05fr)_minmax(0,1fr)]">
            <aside className="relative isolate flex min-h-40 flex-col overflow-hidden bg-primary p-6 text-primary-foreground sm:p-8 lg:min-h-svh lg:p-12 dark:bg-sidebar dark:text-sidebar-foreground">
                <AtriumHallArt className="absolute inset-0 -z-20 size-full motion-safe:animate-in motion-safe:duration-1000 motion-safe:fade-in dark:text-primary" />
                <div className="absolute inset-x-0 bottom-0 -z-10 hidden h-2/3 bg-linear-to-t from-primary via-primary/85 to-transparent lg:block dark:from-sidebar dark:via-sidebar/85" />

                <Link
                    href={home()}
                    className="flex w-fit items-center gap-3 rounded-md text-lg font-medium tracking-tight focus-visible:ring-2 focus-visible:ring-primary-foreground/60 focus-visible:outline-none dark:focus-visible:ring-primary/60"
                >
                    <span className="flex size-9 items-center justify-center overflow-hidden rounded-md bg-primary-foreground text-primary dark:bg-primary dark:text-primary-foreground">
                        <BrandMark className="size-5" />
                    </span>
                    {name}
                </Link>

                <div className="mt-auto hidden max-w-md lg:block">
                    <p className="font-display text-5xl leading-[1.05] tracking-tight text-balance xl:text-6xl">
                        {t('The open hall for your operations.')}
                    </p>
                    <ul className="mt-8 grid gap-3 text-sm opacity-80">
                        {highlights.map(({ icon: Icon, label }) => (
                            <li key={label} className="flex items-center gap-3">
                                <Icon aria-hidden className="size-4 shrink-0" />
                                {t(label)}
                            </li>
                        ))}
                    </ul>
                    <p className="mt-12 text-xs opacity-60">
                        © {currentYear} {name}
                    </p>
                </div>
            </aside>

            <main className="flex items-center justify-center px-6 py-10 sm:px-10 lg:py-12">
                <div className="w-full max-w-sm motion-safe:animate-in motion-safe:duration-500 motion-safe:fade-in motion-safe:slide-in-from-bottom-3">
                    <AnnouncementBanner className="mb-8 rounded-lg border" />
                    <header className="mb-8 grid gap-2">
                        <h1 className="font-display text-4xl leading-tight tracking-tight">
                            {title}
                        </h1>
                        {description && (
                            <p className="text-sm text-muted-foreground">
                                {description}
                            </p>
                        )}
                    </header>
                    {children}
                </div>
            </main>
        </div>
    );
}
