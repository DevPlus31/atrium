import { Link, router, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Bell } from 'lucide-react';
import { useState } from 'react';
import { NotificationItem } from '@/components/notification-item';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Skeleton } from '@/components/ui/skeleton';
import { index, readAll } from '@/routes/notifications';

/**
 * The header bell: the unread count comes with every page, the latest
 * notifications only when it opens (a partial reload of the current page).
 */
export function NotificationBell() {
    const { t, tChoice } = useLaravelReactI18n();
    const { unreadNotifications, recentNotifications } = usePage().props;
    const [open, setOpen] = useState(false);

    const toggle = (next: boolean) => {
        setOpen(next);

        if (next) {
            router.reload({
                only: ['recentNotifications', 'unreadNotifications'],
            });
        }
    };

    const label =
        unreadNotifications > 0
            ? tChoice(
                  'Notifications (:count unread)|Notifications (:count unread)',
                  unreadNotifications,
              )
            : t('Notifications');

    return (
        <Popover open={open} onOpenChange={toggle}>
            <PopoverTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="relative"
                    aria-label={label}
                    data-test="notification-bell"
                >
                    <Bell />
                    {unreadNotifications > 0 && (
                        <span
                            className="absolute end-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[10px] leading-none font-semibold text-primary-foreground tabular-nums"
                            aria-hidden="true"
                        >
                            {unreadNotifications > 9
                                ? '9+'
                                : unreadNotifications}
                        </span>
                    )}
                </Button>
            </PopoverTrigger>
            <PopoverContent align="end" className="w-80 p-0">
                <div className="flex items-center justify-between border-b px-3 py-2">
                    <p className="text-sm font-medium">{t('Notifications')}</p>
                    {unreadNotifications > 0 && (
                        <Button
                            variant="link"
                            size="sm"
                            className="h-auto p-0 text-xs"
                            onClick={() =>
                                router.patch(
                                    readAll.url(),
                                    {},
                                    { preserveScroll: true },
                                )
                            }
                        >
                            {t('Mark all as read')}
                        </Button>
                    )}
                </div>

                <div className="max-h-96 overflow-y-auto p-1">
                    {recentNotifications === undefined ? (
                        <div className="grid gap-2 p-2" aria-hidden="true">
                            <Skeleton className="h-10 w-full" />
                            <Skeleton className="h-10 w-full" />
                            <Skeleton className="h-10 w-full" />
                        </div>
                    ) : recentNotifications.length === 0 ? (
                        <p className="px-3 py-6 text-center text-sm text-muted-foreground">
                            {t("You're all caught up.")}
                        </p>
                    ) : (
                        recentNotifications.map((notification) => (
                            <NotificationItem
                                key={notification.id}
                                notification={notification}
                                onClick={() => setOpen(false)}
                            />
                        ))
                    )}
                </div>

                <div className="border-t p-1">
                    <Button
                        asChild
                        variant="ghost"
                        size="sm"
                        className="w-full"
                    >
                        <Link href={index()} onClick={() => setOpen(false)}>
                            {t('View all notifications')}
                        </Link>
                    </Button>
                </div>
            </PopoverContent>
        </Popover>
    );
}
