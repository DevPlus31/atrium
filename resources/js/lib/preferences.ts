import { router } from '@inertiajs/react';
import { update } from '@/routes/preferences';
import type { LayoutConfig } from '@/types/admin';

type Appearance = App.Enums.Appearance;
type ThemePreset = App.Enums.ThemePreset;

/** The fields PATCH settings/preferences accepts; each one is optional. */
export type PreferenceChanges = {
    appearance?: Appearance;
    theme?: ThemePreset;
    layout?: Partial<LayoutConfig>;
    locale?: string;
    /** An IANA zone, or null for the browser's own. */
    timezone?: string | null;
};

// Records (not lists) so a value added to the PHP enum fails type-checking
// here until the client knows about it.
const appearances: Record<Appearance, true> = {
    light: true,
    dark: true,
    system: true,
};

const themePresets: Record<ThemePreset, true> = {
    default: true,
    ember: true,
    contrast: true,
};

export const isAppearance = (value: unknown): value is Appearance =>
    typeof value === 'string' && Object.hasOwn(appearances, value);

export const isThemePreset = (value: unknown): value is ThemePreset =>
    typeof value === 'string' && Object.hasOwn(themePresets, value);

export const setCookie = (name: string, value: string, days = 365): void => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${encodeURIComponent(value)};path=/;max-age=${maxAge};SameSite=Lax`;
};

export const getCookie = (name: string): string | null => {
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

/**
 * Persist preference changes for the signed-in user (PATCH
 * settings/preferences) without disturbing the current page.
 */
export const persistPreferences = (changes: PreferenceChanges): void => {
    router.patch(update.url(), changes, {
        preserveScroll: true,
        preserveState: true,
    });
};
