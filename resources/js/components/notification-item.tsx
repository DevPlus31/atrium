import { Link } from '@inertiajs/react';
import { useFormatters } from '@/hooks/use-formatters';
import { cn } from '@/lib/utils';
import { update } from '@/routes/notifications';
import type { AppNotification } from '@/types/admin';

/**
 * One notification. Clicking it marks it read and follows its link (the
 * server decides where to go, so a link never leaves the app).
 */
export function NotificationItem({
    notification,
    className,
    onClick,
}: {
    notification: AppNotification;
    className?: string;
    onClick?: () => void;
}) {
    const { shortDateTime } = useFormatters();
    const unread = notification.read_at === null;

    return (
        <Link
            href={update(notification.id)}
            as="button"
            preserveScroll
            onClick={onClick}
            data-test="notification"
            className={cn(
                'flex w-full items-start gap-3 rounded-md px-3 py-2.5 text-start transition-colors hover:bg-accent focus-visible:bg-accent focus-visible:outline-none',
                className,
            )}
        >
            <span
                className={cn(
                    'mt-1.5 size-2 shrink-0 rounded-full',
                    unread ? 'bg-primary' : 'bg-transparent',
                )}
                aria-hidden="true"
            />
            <span className="grid min-w-0 flex-1 gap-0.5">
                <span
                    className={cn(
                        'text-sm',
                        unread ? 'font-medium' : 'text-muted-foreground',
                    )}
                >
                    {notification.title}
                </span>
                {notification.body && (
                    <span className="line-clamp-2 text-sm text-muted-foreground">
                        {notification.body}
                    </span>
                )}
                <time
                    dateTime={notification.created_at}
                    className="text-xs text-muted-foreground"
                >
                    {shortDateTime(notification.created_at)}
                </time>
            </span>
        </Link>
    );
}
