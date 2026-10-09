import { Head, InfiniteScroll, Link, router, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { BellOff } from 'lucide-react';
import { NotificationItem } from '@/components/notification-item';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { edit as editPreferences } from '@/routes/notification-preferences';
import { index, readAll } from '@/routes/notifications';
import type { BreadcrumbItem } from '@/types';
import type { AppNotification } from '@/types/admin';

type NotificationsProps = {
    notifications: { data: AppNotification[] };
};

export default function Notifications({ notifications }: NotificationsProps) {
    const { t, tChoice } = useLaravelReactI18n();
    const { unreadNotifications } = usePage().props;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('Notifications'), href: index() },
    ];
    useBreadcrumbs(breadcrumbs);

    return (
        <>
            <Head title={t('Notifications')} />
            <div className="grid max-w-2xl gap-6">
                <Card>
                    <CardHeader className="flex flex-row items-start justify-between gap-4">
                        <div className="grid gap-1.5">
                            <CardTitle>
                                <h1>{t('Notifications')}</h1>
                            </CardTitle>
                            <CardDescription>
                                {unreadNotifications > 0
                                    ? tChoice(
                                          ':count unread|:count unread',
                                          unreadNotifications,
                                      )
                                    : t("You're all caught up.")}
                            </CardDescription>
                        </div>
                        <div className="flex shrink-0 items-center gap-2">
                            <Button asChild variant="ghost" size="sm">
                                <Link href={editPreferences()}>
                                    {t('Email settings')}
                                </Link>
                            </Button>
                            {unreadNotifications > 0 && (
                                <Button
                                    variant="outline"
                                    size="sm"
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
                    </CardHeader>
                    <CardContent className="px-3">
                        {notifications.data.length === 0 ? (
                            <div className="flex flex-col items-center gap-2 py-10 text-center text-muted-foreground">
                                <BellOff
                                    className="size-6"
                                    aria-hidden="true"
                                />
                                <p className="text-sm">
                                    {t('No notifications yet.')}
                                </p>
                            </div>
                        ) : (
                            <InfiniteScroll
                                data="notifications"
                                className="grid gap-1"
                                loading={() => (
                                    <div className="flex justify-center py-4">
                                        <Spinner />
                                    </div>
                                )}
                            >
                                {notifications.data.map((notification) => (
                                    <NotificationItem
                                        key={notification.id}
                                        notification={notification}
                                    />
                                ))}
                            </InfiniteScroll>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
