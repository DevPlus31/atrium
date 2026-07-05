import { router, usePage } from '@inertiajs/react';
import { useEffect, useSyncExternalStore } from 'react';
import { update } from '@/routes/preferences';
import type { LayoutConfig } from '@/types/admin';

export type Appearance = App.Enums.Appearance;
export type ResolvedAppearance = Exclude<Appearance, 'system'>;
export type ThemePreset = App.Enums.ThemePreset;

export type UseAppearanceReturn = {
    readonly appearance: Appearance;
    readonly resolvedAppearance: ResolvedAppearance;
    readonly updateAppearance: (mode: Appearance) => void;
};

export type UseThemePreferenceReturn = UseAppearanceReturn & {
    readonly theme: ThemePreset;
    readonly updateTheme: (preset: ThemePreset) => void;
    readonly layout: LayoutConfig;
    readonly updateLayout: (options: Partial<LayoutConfig>) => void;
};

const listeners = new Set<() => void>();

// The server is the source of truth (cookie for guests, user columns when
// authenticated); this module state only bridges the gap between a setter
// call and the next server render.
let currentAppearance: Appearance = 'system';
let currentTheme: ThemePreset = 'default';

const prefersDark = (): boolean => {
    if (typeof window === 'undefined') {
        return false;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches;
};

const setCookie = (name: string, value: string, days = 365): void => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${encodeURIComponent(value)};path=/;max-age=${maxAge};SameSite=Lax`;
};

const getCookie = (name: string): string | null => {
    if (typeof document === 'undefined') {
        return null;
    }

    const match = document.cookie
        .split('; ')
        .find((row) => row.startsWith(`${name}=`));

    return match
        ? decodeURIComponent(match.split('=').slice(1).join('='))
        : null;
};

const isAppearance = (value: unknown): value is Appearance =>
    value === 'light' || value === 'dark' || value === 'system';

const isThemePreset = (value: unknown): value is ThemePreset =>
    value === 'default' || value === 'ember' || value === 'contrast';

const isDarkMode = (appearance: Appearance): boolean => {
    return appearance === 'dark' || (appearance === 'system' && prefersDark());
};

const applyAppearance = (appearance: Appearance): void => {
    if (typeof document === 'undefined') {
        return;
    }

    const isDark = isDarkMode(appearance);

    document.documentElement.classList.toggle('dark', isDark);
    document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
};

const applyThemePreset = (preset: ThemePreset): void => {
    if (typeof document === 'undefined') {
        return;
    }

    if (preset === 'default') {
        document.documentElement.removeAttribute('data-theme');

        return;
    }

    document.documentElement.setAttribute('data-theme', preset);
};

const setAppearance = (mode: Appearance): void => {
    currentAppearance = mode;

    setCookie('appearance', mode);
    applyAppearance(mode);
    notify();
};

const setThemePreset = (preset: ThemePreset): void => {
    currentTheme = preset;

    setCookie('theme', preset);
    applyThemePreset(preset);
    notify();
};

const subscribe = (callback: () => void) => {
    listeners.add(callback);

    return () => listeners.delete(callback);
};

const notify = (): void => listeners.forEach((listener) => listener());

const mediaQuery = (): MediaQueryList | null => {
    if (typeof window === 'undefined') {
        return null;
    }

    return window.matchMedia('(prefers-color-scheme: dark)');
};

const handleSystemThemeChange = (): void => applyAppearance(currentAppearance);

export function initializeTheme(): void {
    if (typeof window === 'undefined') {
        return;
    }

    const appearanceCookie = getCookie('appearance');
    const themeCookie = getCookie('theme');

    currentAppearance = isAppearance(appearanceCookie)
        ? appearanceCookie
        : 'system';
    currentTheme = isThemePreset(themeCookie) ? themeCookie : 'default';

    applyAppearance(currentAppearance);

    // Set up system theme change listener
    mediaQuery()?.addEventListener('change', handleSystemThemeChange);
}

/**
 * Read-mostly appearance state, safe outside the Inertia component tree
 * (e.g. the global toaster). `updateAppearance` here persists the cookie
 * only; the admin shell uses `useThemePreference` so changes also mirror
 * to the authenticated user's preference columns.
 */
export function useAppearance(): UseAppearanceReturn {
    const appearance: Appearance = useSyncExternalStore(
        subscribe,
        () => currentAppearance,
        () => 'system',
    );

    const resolvedAppearance: ResolvedAppearance = isDarkMode(appearance)
        ? 'dark'
        : 'light';

    return {
        appearance,
        resolvedAppearance,
        updateAppearance: setAppearance,
    } as const;
}

/**
 * The theme-preference hook (docs/specs/theming.md): current appearance,
 * preset, and layout config plus setters. Setters stamp the root element
 * immediately, persist to cookies for guests, and mirror to the user's
 * preference columns when authenticated. Must render inside the Inertia
 * component tree.
 */
export function useThemePreference(): UseThemePreferenceReturn {
    const {
        auth,
        appearance: serverAppearance,
        theme: serverTheme,
        layout,
    } = usePage().props;
    const { appearance, resolvedAppearance } = useAppearance();

    const theme: ThemePreset = useSyncExternalStore(
        subscribe,
        () => currentTheme,
        () => (isThemePreset(serverTheme) ? serverTheme : 'default'),
    );

    // A fresh server render (another device, login) wins over local state.
    useEffect(() => {
        if (
            isAppearance(serverAppearance) &&
            serverAppearance !== currentAppearance
        ) {
            currentAppearance = serverAppearance;
            applyAppearance(serverAppearance);
            notify();
        }
    }, [serverAppearance]);

    useEffect(() => {
        if (isThemePreset(serverTheme) && serverTheme !== currentTheme) {
            currentTheme = serverTheme;
            applyThemePreset(serverTheme);
            notify();
        }
    }, [serverTheme]);

    const patchOptions = {
        preserveScroll: true,
        preserveState: true,
    } as const;

    const updateAppearance = (mode: Appearance): void => {
        setAppearance(mode);

        if (auth.user) {
            router.patch(update.url(), { appearance: mode }, patchOptions);
        }
    };

    const updateTheme = (preset: ThemePreset): void => {
        setThemePreset(preset);

        if (auth.user) {
            router.patch(update.url(), { theme: preset }, patchOptions);
        }
    };

    const updateLayout = (options: Partial<LayoutConfig>): void => {
        setCookie('layout', JSON.stringify({ ...layout, ...options }));

        if (options.direction && typeof document !== 'undefined') {
            document.documentElement.dir = options.direction;
        }

        if (auth.user) {
            router.patch(update.url(), { layout: options }, patchOptions);
        }
    };

    return {
        appearance,
        resolvedAppearance,
        updateAppearance,
        theme,
        updateTheme,
        layout,
        updateLayout,
    } as const;
}
