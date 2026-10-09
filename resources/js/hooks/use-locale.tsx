import { router, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useEffect, useRef } from 'react';
import { persistPreferences, setCookie } from '@/lib/preferences';

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
    const i18n = useLaravelReactI18n();
    const { currentLocale, setLocale } = i18n;

    // The i18n provider hands out new function identities on every render,
    // so read them through a ref: an effect keyed on them would re-run right
    // after a local switch and undo it before the server has caught up.
    const i18nRef = useRef(i18n);

    useEffect(() => {
        i18nRef.current = i18n;
    });

    // A fresh server locale (another device, login) wins over local state.
    useEffect(() => {
        if (serverLocale !== i18nRef.current.currentLocale()) {
            i18nRef.current.setLocale(serverLocale);
        }

        if (typeof document !== 'undefined') {
            document.documentElement.lang = serverLocale;
        }
    }, [serverLocale]);

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
            persistPreferences({ locale });
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
