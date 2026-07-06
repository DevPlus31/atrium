import { useLaravelReactI18n } from 'laravel-react-i18n';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useInitials } from '@/hooks/use-initials';

type RecentUsersWidgetProps = {
    data: Modules.Users.Data.RecentUsersWidgetData;
};

const dateFormatter = new Intl.DateTimeFormat(undefined, {
    month: 'short',
    day: 'numeric',
});

export default function RecentUsersWidget({ data }: RecentUsersWidgetProps) {
    const { t } = useLaravelReactI18n();
    const getInitials = useInitials();

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
                                <span className="flex size-8 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-medium text-muted-foreground">
                                    {getInitials(user.name)}
                                </span>
                                <span className="min-w-0 flex-1">
                                    <span className="block truncate text-sm font-medium">
                                        {user.name}
                                    </span>
                                    <span className="block truncate text-xs text-muted-foreground">
                                        {user.email}
                                    </span>
                                </span>
                                <span className="text-xs text-muted-foreground">
                                    {dateFormatter.format(
                                        new Date(user.created_at),
                                    )}
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
            </CardContent>
        </Card>
    );
}
