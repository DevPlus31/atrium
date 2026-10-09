import { useLaravelReactI18n } from 'laravel-react-i18n';
import { EmptyState } from '@/components/empty-state';
import { UserInfo } from '@/components/user-info';
import { WidgetCard } from '@/components/widget-card';
import { useFormatters } from '@/hooks/use-formatters';

type RecentUsersWidgetProps = {
    data: Modules.Users.Data.RecentUsersWidgetData;
};

export default function RecentUsersWidget({ data }: RecentUsersWidgetProps) {
    const { t } = useLaravelReactI18n();
    const format = useFormatters();

    return (
        <WidgetCard kicker={t('Recent users')} title={t('Latest sign-ups')}>
            {data.users.length === 0 ? (
                <EmptyState>{t('No users yet.')}</EmptyState>
            ) : (
                <ul className="flex flex-col gap-3">
                    {data.users.map((user) => (
                        <li key={user.id} className="flex items-center gap-3">
                            <UserInfo user={user} showEmail />
                            <span className="text-xs text-muted-foreground">
                                {format.shortDate(user.created_at)}
                            </span>
                        </li>
                    ))}
                </ul>
            )}
        </WidgetCard>
    );
}
