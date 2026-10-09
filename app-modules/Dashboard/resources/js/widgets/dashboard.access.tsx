import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Hourglass } from 'lucide-react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

type AccessStatusWidgetProps = {
    data: Modules.Dashboard.Data.AccessStatusWidgetData;
};

export default function AccessStatusWidget({ data }: AccessStatusWidgetProps) {
    const { t } = useLaravelReactI18n();

    return (
        <Card className="border-primary/30 bg-primary/5 md:col-span-2 xl:col-span-3">
            <CardHeader className="flex flex-row items-start gap-4">
                <span className="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                    <Hourglass className="size-5" aria-hidden="true" />
                </span>
                <div className="space-y-1.5">
                    <CardTitle className="text-base">
                        {t('Waiting for access')}
                    </CardTitle>
                    <CardDescription>
                        {t(
                            "Your account is set up, but an administrator hasn't given it any access yet. There's nothing you need to do.",
                        )}
                    </CardDescription>
                </div>
            </CardHeader>
            <CardContent className="text-sm text-muted-foreground">
                {t('If they ask, your account email is :email.', {
                    email: data.email,
                })}
            </CardContent>
        </Card>
    );
}
