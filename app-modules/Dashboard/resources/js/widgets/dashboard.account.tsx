import { useLaravelReactI18n } from 'laravel-react-i18n';
import { CircleAlert, CircleCheck } from 'lucide-react';
import type { ReactNode } from 'react';
import TextLink from '@/components/text-link';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useFormatters } from '@/hooks/use-formatters';
import { show as passkeys } from '@/routes/passkeys';
import { show as twoFactor } from '@/routes/two-factor';
import { edit as profile } from '@/routes/user-profile';
import type { RouteDefinition } from '@/wayfinder';

type AccountOverviewWidgetProps = {
    data: Modules.Dashboard.Data.AccountOverviewWidgetData;
};

function StatusRow({
    ok,
    label,
    detail,
    href,
    action,
}: {
    ok: boolean;
    label: string;
    detail: ReactNode;
    href: RouteDefinition<'get'>;
    action: string;
}) {
    const Icon = ok ? CircleCheck : CircleAlert;

    return (
        <li className="flex items-center gap-3">
            <Icon
                className={
                    ok
                        ? 'size-5 shrink-0 text-primary'
                        : 'size-5 shrink-0 text-muted-foreground'
                }
                aria-hidden="true"
            />
            <span className="min-w-0 flex-1">
                <span className="block text-sm font-medium">{label}</span>
                <span className="block text-xs text-muted-foreground">
                    {detail}
                </span>
            </span>
            <TextLink href={href} className="text-sm font-medium">
                {action}
            </TextLink>
        </li>
    );
}

export default function AccountOverviewWidget({
    data,
}: AccountOverviewWidgetProps) {
    const { t, tChoice } = useLaravelReactI18n();
    const format = useFormatters();

    return (
        <Card>
            <CardHeader>
                <CardDescription>{t('Your account')}</CardDescription>
                <CardTitle className="text-base">
                    {t('Sign-in security')}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <ul className="flex flex-col gap-4">
                    <StatusRow
                        ok={data.email_verified}
                        label={t('Email address')}
                        detail={
                            data.email_verified
                                ? t('Verified')
                                : t('Not verified')
                        }
                        href={profile()}
                        action={t('Profile')}
                    />
                    <StatusRow
                        ok={data.two_factor_enabled}
                        label={t('Two-factor authentication')}
                        detail={data.two_factor_enabled ? t('On') : t('Off')}
                        href={twoFactor()}
                        action={
                            data.two_factor_enabled ? t('Manage') : t('Set up')
                        }
                    />
                    <StatusRow
                        ok={data.passkeys > 0}
                        label={t('Passkeys')}
                        detail={
                            data.passkeys > 0
                                ? tChoice(
                                      ':count passkey|:count passkeys',
                                      data.passkeys,
                                  )
                                : t('None yet')
                        }
                        href={passkeys()}
                        action={data.passkeys > 0 ? t('Manage') : t('Add')}
                    />
                </ul>
            </CardContent>
            <CardFooter className="text-xs text-muted-foreground">
                {t('Member since :date', {
                    date: format.longDate(data.member_since),
                })}
            </CardFooter>
        </Card>
    );
}
