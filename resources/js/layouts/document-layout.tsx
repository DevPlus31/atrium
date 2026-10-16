import { Link, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { ArrowLeft } from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { BrandMark } from '@/components/brand-mark';
import { ConsentBanner } from '@/components/privacy/consent-banner';
import { LegalLinks } from '@/components/privacy/legal-links';
import { home } from '@/routes';

type DocumentLayoutProps = {
    title: string;
    children: ReactNode;
};

/**
 * A long-form reading page open to everyone (the legal documents): the brand
 * on top, one readable column, the legal links at the foot.
 */
export default function DocumentLayout({
    title,
    children,
}: DocumentLayoutProps) {
    const { t } = useLaravelReactI18n();
    const { name } = usePage().props;
    // Per mount, not per module: a long-lived SSR process spans New Year.
    const [currentYear] = useState(() => new Date().getFullYear());

    return (
        <div className="flex min-h-svh flex-col bg-background">
            <header className="border-b">
                <div className="mx-auto flex h-16 w-full max-w-3xl items-center justify-between gap-4 px-6">
                    <Link
                        href={home()}
                        className="flex items-center gap-2 rounded-md text-sm font-medium focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <BrandMark className="size-6 text-primary" />
                        {name}
                    </Link>
                    <Link
                        href={home()}
                        className="flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground"
                    >
                        <ArrowLeft
                            aria-hidden
                            className="size-4 rtl:rotate-180"
                        />
                        {t('Back to the app')}
                    </Link>
                </div>
            </header>

            <main className="mx-auto w-full max-w-3xl flex-1 px-6 py-12 sm:py-16">
                <h1 className="mb-10 font-display text-5xl leading-[1.05] tracking-tight text-balance sm:text-6xl">
                    {title}
                </h1>
                {children}
            </main>

            <footer className="border-t">
                <div className="mx-auto flex w-full max-w-3xl flex-wrap items-center justify-between gap-4 px-6 py-6">
                    <LegalLinks />
                    <p className="text-xs text-muted-foreground">
                        © {currentYear} {name}
                    </p>
                </div>
            </footer>

            <ConsentBanner />
        </div>
    );
}
