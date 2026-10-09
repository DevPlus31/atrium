import { router, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Info, TriangleAlert, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { dismiss } from '@/routes/announcement';

/**
 * The site-wide announcement (Settings → Announcement), on every page until
 * it ends or the viewer dismisses it. Dismissing sets a cookie on the server,
 * so the next page renders without it.
 */
export function AnnouncementBanner({ className }: { className?: string }) {
    const { t } = useLaravelReactI18n();
    const { announcement } = usePage().props;

    if (announcement === null) {
        return null;
    }

    const warning = announcement.level === 'warning';
    const LevelIcon = warning ? TriangleAlert : Info;

    return (
        <div
            role="status"
            data-test="announcement"
            className={cn(
                'flex w-full shrink-0 items-center gap-3 border-b px-4 py-2 text-sm',
                warning
                    ? 'border-destructive/20 bg-destructive/10 text-destructive'
                    : 'border-primary/15 bg-primary/5 text-foreground',
                className,
            )}
        >
            <LevelIcon aria-hidden className="size-4 shrink-0" />
            <p className="flex-1 whitespace-pre-line">{announcement.message}</p>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                className="size-7 shrink-0"
                aria-label={t('Dismiss announcement')}
                onClick={() =>
                    router.post(
                        dismiss.url(),
                        {},
                        {
                            preserveScroll: true,
                            // Gone at once; Inertia restores it if the
                            // request fails.
                            optimistic: () => ({ announcement: null }),
                        },
                    )
                }
            >
                <X />
            </Button>
        </div>
    );
}
