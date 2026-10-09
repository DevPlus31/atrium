import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { ComponentType } from 'react';
import AdminLayout from '@/layouts/admin-layout';

const appPages = import.meta.glob<ComponentType>('../pages/**/*.tsx');
const modulePages = import.meta.glob<ComponentType>(
    '../../../app-modules/*/resources/js/pages/**/*.tsx',
);

/**
 * Resolves Inertia page components from both the app and app-modules.
 *
 * Module pages are addressed as `<module>::<path>` (e.g. `users::index`),
 * matched case-insensitively against `app-modules/<Module>/resources/js/pages`.
 */
export function resolvePage(name: string): Promise<ComponentType> {
    if (!name.includes('::')) {
        return resolvePageComponent<ComponentType>(
            `../pages/${name}.tsx`,
            appPages,
        );
    }

    const [module = '', path = ''] = name.split('::', 2);
    const expected =
        `../../../app-modules/${module}/resources/js/pages/${path}.tsx`.toLowerCase();
    const page = Object.entries(modulePages).find(
        ([key]) => key.toLowerCase() === expected,
    );

    if (page === undefined) {
        throw new Error(`Module page not found: ${name}`);
    }

    return page[1]();
}

/**
 * App pages (outside app-modules) that render in the admin shell; every other
 * app page (login, password reset, 2FA challenge…) keeps its own layout.
 */
const adminAppPages = new Set([
    'appearance/update',
    'user-notification-preferences/edit',
    'user-notifications/index',
    'user-passkeys/show',
    'user-password/edit',
    'user-profile/edit',
    'user-sessions/index',
    'user-two-factor-authentication/show',
]);

/**
 * The persistent layout for a page: every module page and the account
 * settings pages share one AdminLayout instance across visits. Module pages
 * under `pages/public/` (e.g. `users::public/accept-invitation`) are for
 * people without an account and bring their own layout.
 */
export function resolveLayout(name: string): typeof AdminLayout | null {
    if (name.includes('::')) {
        return name.split('::', 2)[1]?.startsWith('public/')
            ? null
            : AdminLayout;
    }

    return adminAppPages.has(name) ? AdminLayout : null;
}
