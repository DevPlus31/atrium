import type { VisitOptions } from '@inertiajs/core';
import { router, usePage } from '@inertiajs/react';
import { store } from '@/routes/consent';

type ConsentCategory = App.Enums.ConsentCategory;

/**
 * The visitor's cookie consent (the `consent` shared prop, resolved on the
 * server). Load an optional script only once `allows()` says so: nothing
 * optional may run before the visitor chooses, and a Global Privacy Control
 * signal has already switched off the categories that sell or share data.
 *
 * @example
 * const { allows } = useConsent();
 * useEffect(() => { if (allows('analytics')) loadAnalytics(); }, [allows]);
 */
export function useConsent() {
    const { consent } = usePage().props;

    return {
        ...consent,
        allows: (category: ConsentCategory): boolean =>
            consent.granted.includes(category),
    };
}

/**
 * Save a cookie choice: the categories allowed, every other one refused.
 */
export function saveConsent(
    granted: ConsentCategory[],
    options: Omit<VisitOptions, 'data' | 'method'> = {},
): void {
    router.post(store.url(), { granted }, { preserveScroll: true, ...options });
}
