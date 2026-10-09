import { useLaravelReactI18n } from 'laravel-react-i18n';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useFormatters } from '@/hooks/use-formatters';

type AccountActivityWidgetProps = {
    data: Modules.Audit.Data.AccountActivityWidgetData;
};

/** Sentence per audit event, with the acting person as `:name`. */
const eventSentences: Record<string, string> = {
    created: ':name created your account',
    updated: ':name updated your account',
    impersonated: ':name signed in as you',
};

export default function AccountActivityWidget({
    data,
}: AccountActivityWidgetProps) {
    const { t } = useLaravelReactI18n();
    const format = useFormatters();

    return (
        <Card>
            <CardHeader>
                <CardDescription>{t('Recent activity')}</CardDescription>
                <CardTitle className="text-base">
                    {t('Changes to your account')}
                </CardTitle>
            </CardHeader>
            <CardContent>
                {data.entries.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        {t('No one has changed your account.')}
                    </p>
                ) : (
                    <ol className="relative flex flex-col gap-4 border-s ps-4">
                        {data.entries.map((entry) => (
                            <li key={entry.id} className="relative">
                                <span
                                    className="absolute -start-[21px] top-1.5 size-2.5 rounded-full border-2 border-card bg-primary"
                                    aria-hidden="true"
                                />
                                <p className="text-sm">
                                    {t(
                                        eventSentences[entry.event ?? ''] ??
                                            ':name changed your account',
                                        {
                                            name:
                                                entry.causer ??
                                                t('An administrator'),
                                        },
                                    )}
                                </p>
                                <time
                                    dateTime={entry.created_at}
                                    className="text-xs text-muted-foreground"
                                >
                                    {format.shortDateTime(entry.created_at)}
                                </time>
                            </li>
                        ))}
                    </ol>
                )}
            </CardContent>
        </Card>
    );
}
