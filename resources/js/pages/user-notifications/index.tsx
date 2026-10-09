import { Head, InfiniteScroll, Link, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { BellOff } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { MarkAllReadButton } from '@/components/mark-all-read-button';
import { NotificationItem } from '@/components/notification-item';
import { PageStack, SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { edit as editPreferences } from '@/routes/notification-preferences';
import { index } from '@/routes/notifications';
import type { AppNotification } from '@/types/admin';

type NotificationsProps = {
    notifications: { data: AppNotification[] };
};

export default function Notifications({ notifications }: NotificationsProps) {
    const { t, tChoice } = useLaravelReactI18n();
    const { unreadNotifications } = usePage().props;

    useBreadcrumbs({ title: t('Notifications'), href: index() });

    return (
        <>
            <Head title={t('Notifications')} />
            <PageStack>
                <SectionCard
                    title={<h1>{t('Notifications')}</h1>}
                    description={
                        unreadNotifications > 0
                            ? tChoice(
                                  ':count unread|:count unread',
                                  unreadNotifications,
                              )
                            : t("You're all caught up.")
                    }
                    actions={
                        <>
                            <Button asChild variant="ghost" size="sm">
                                <Link href={editPreferences()}>
                                    {t('Email settings')}
                                </Link>
                            </Button>
                            {unreadNotifications > 0 && (
                                <MarkAllReadButton
                                    variant="outline"
                                    size="sm"
                                />
                            )}
                        </>
                    }
                    contentClassName="px-3"
                >
                    {notifications.data.length === 0 ? (
                        <EmptyState icon={BellOff}>
                            {t('No notifications yet.')}
                        </EmptyState>
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
                </SectionCard>
            </PageStack>
        </>
    );
}
