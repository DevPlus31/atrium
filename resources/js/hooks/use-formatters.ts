import { usePage } from '@inertiajs/react';
import { useMemo, useSyncExternalStore } from 'react';
import { createFormatters } from '@/lib/format';
import type { Formatters } from '@/lib/format';

const noSubscription = (): (() => void) => () => {};

/**
 * Formatters in the app's chosen language and the user's timezone. A user
 * who picked a timezone gets it everywhere, server render included; for
 * everyone else the server renders UTC (the only zone it can know) and the
 * browser's zone takes over right after hydration.
 */
export function useFormatters(): Formatters {
    const { locale, timezone } = usePage().props;
    const hydrated = useSyncExternalStore(
        noSubscription,
        () => true,
        () => false,
    );

    return useMemo(
        () =>
            createFormatters(
                locale,
                timezone ?? (hydrated ? undefined : 'UTC'),
            ),
        [locale, timezone, hydrated],
    );
}
