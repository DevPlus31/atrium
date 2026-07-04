import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { ComponentType } from 'react';

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
