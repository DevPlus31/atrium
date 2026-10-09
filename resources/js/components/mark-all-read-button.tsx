import { router } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { ComponentProps } from 'react';
import { Button } from '@/components/ui/button';
import { readAll } from '@/routes/notifications';

/** "Mark all as read" for the signed-in user's notifications. */
export function MarkAllReadButton(
    props: Pick<
        ComponentProps<typeof Button>,
        'variant' | 'size' | 'className'
    >,
) {
    const { t } = useLaravelReactI18n();

    return (
        <Button
            {...props}
            onClick={() =>
                router.patch(readAll.url(), {}, { preserveScroll: true })
            }
        >
            {t('Mark all as read')}
        </Button>
    );
}
