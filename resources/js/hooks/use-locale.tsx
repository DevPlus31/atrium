import { router, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useEffect } from 'react';
import { setCookie } from '@/hooks/use-appearance';
import { update } from '@/routes/preferences';

export type UseLocalePreferenceReturn = {
    readonly locale: string;
    readonly locales: Record<string, string>;
    readonly updateLocale: (locale: string) => void;
};

/**
 * The locale preference: the active locale, the available locales
 * (config/app.php `available_locales`, shared via Inertia props), and a
 * setter. The setter switches client-side translations immediately, persists
 * the choice like every other preference (cookie for first paint, user
 * column when authenticated), and re-renders the current page so
 * server-sent strings (nav labels, flashes) pick up the new locale.
 */
export function useLocalePreference(): UseLocalePreferenceReturn {
    const { auth, locale: serverLocale, locales } = usePage().props;
    const { currentLocale, setLocale } = useLaravelReactI18n();

    // A fresh server render (another device, login) wins over local state.
    useEffect(() => {
        if (serverLocale !== currentLocale()) {
            setLocale(serverLocale);
        }

        if (typeof document !== 'undefined') {
            document.documentElement.lang = serverLocale;
        }
    }, [serverLocale, currentLocale, setLocale]);

    const updateLocale = (locale: string): void => {
        if (!(locale in locales)) {
            return;
        }

        setCookie('locale', locale);
        setLocale(locale);

        if (typeof document !== 'undefined') {
            document.documentElement.lang = locale;
        }

        if (auth.user) {
            router.patch(
                update.url(),
                { locale },
                { preserveScroll: true, preserveState: true },
            );
        } else {
            router.reload();
        }
    };

    return {
        locale: currentLocale(),
        locales,
        updateLocale,
    } as const;
}
