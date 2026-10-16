import { Link } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { show } from '@/routes/legal';

/**
 * The legal footer links. "Your privacy choices" is the opt-out link US
 * state privacy laws (CCPA/CPRA and others) ask for on every page that
 * collects personal information; it opens the cookie choices.
 */
export function LegalLinks({ className }: { className?: string }) {
    const { t } = useLaravelReactI18n();

    const links = [
        { label: t('Terms of Service'), href: show('terms') },
        { label: t('Privacy Policy'), href: show('privacy') },
        { label: t('Cookie Policy'), href: show('cookies') },
        {
            label: t('Your privacy choices'),
            href: `${show.url('cookies')}#choices`,
        },
    ];

    return (
        <nav aria-label={t('Legal')} className={className}>
            <ul className="flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground">
                {links.map((link) => (
                    <li key={link.label}>
                        <Link
                            href={link.href}
                            className="underline-offset-4 transition-colors hover:text-foreground hover:underline"
                        >
                            {link.label}
                        </Link>
                    </li>
                ))}
            </ul>
        </nav>
    );
}
