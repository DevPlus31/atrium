import { useLaravelReactI18n } from 'laravel-react-i18n';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { UserAvatar } from '@/components/user-avatar';
import { useFormatters } from '@/hooks/use-formatters';

type RecentUsersWidgetProps = {
    data: Modules.Users.Data.RecentUsersWidgetData;
};

export default function RecentUsersWidget({ data }: RecentUsersWidgetProps) {
    const { t } = useLaravelReactI18n();
    const format = useFormatters();

    return (
        <Card>
            <CardHeader>
                <CardDescription>{t('Recent users')}</CardDescription>
                <CardTitle className="text-base">
                    {t('Latest sign-ups')}
                </CardTitle>
            </CardHeader>
            <CardContent>
                {data.users.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        {t('No users yet.')}
                    </p>
                ) : (
                    <ul className="flex flex-col gap-3">
                        {data.users.map((user) => (
                            <li
                                key={user.id}
                                className="flex items-center gap-3"
                            >
                                <UserAvatar
                                    name={user.name}
                                    avatar={user.avatar}
                                />
                                <span className="min-w-0 flex-1">
                                    <span className="block truncate text-sm font-medium">
                                        {user.name}
                                    </span>
                                    <span className="block truncate text-xs text-muted-foreground">
                                        {user.email}
                                    </span>
                                </span>
                                <span className="text-xs text-muted-foreground">
                                    {format.shortDate(user.created_at)}
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
            </CardContent>
        </Card>
    );
}
