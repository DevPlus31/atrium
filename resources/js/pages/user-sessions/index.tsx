import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Monitor, Smartphone } from 'lucide-react';
import { ItemList, ItemRow } from '@/components/item-list';
import { PasswordConfirmDialog } from '@/components/password-confirm-dialog';
import { SettingsSection } from '@/components/settings-section';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useFormatters } from '@/hooks/use-formatters';
import { SettingsPage } from '@/layouts/settings/page';
import { destroy, index } from '@/routes/sessions';

type Session = App.Modules.Data.SessionData;

type SessionsProps = {
    /** False when sessions are not stored in the database. */
    listable: boolean;
    sessions: Session[];
};

export default function Sessions({ listable, sessions }: SessionsProps) {
    const { t } = useLaravelReactI18n();
    const { dateTime } = useFormatters();

    const deviceName = (session: Session): string => {
        if (session.browser && session.platform) {
            return t(':browser on :platform', {
                browser: session.browser,
                platform: session.platform,
            });
        }

        return session.browser ?? session.platform ?? t('Unknown device');
    };

    return (
        <>
            <SettingsPage title={t('Sessions')} href={index()}>
                <SettingsSection
                    title={t('Browser sessions')}
                    description={t(
                        'Where you are signed in. If you see a device you don’t recognize, sign out of your other sessions and change your password.',
                    )}
                >
                    {listable && (
                        <ItemList>
                            {sessions.map((session, position) => {
                                const DeviceIcon = session.is_mobile
                                    ? Smartphone
                                    : Monitor;

                                return (
                                    <ItemRow
                                        key={`${session.last_active}-${position}`}
                                        test="session"
                                        icon={
                                            <DeviceIcon
                                                className="size-6 shrink-0 text-muted-foreground"
                                                aria-hidden="true"
                                            />
                                        }
                                        title={deviceName(session)}
                                        details={[
                                            session.ip_address,
                                            !session.is_current &&
                                                t('Last active :date', {
                                                    date: dateTime(
                                                        session.last_active,
                                                    ),
                                                }),
                                        ]}
                                        actions={
                                            session.is_current ? (
                                                <Badge variant="secondary">
                                                    {t('This device')}
                                                </Badge>
                                            ) : undefined
                                        }
                                    />
                                );
                            })}
                        </ItemList>
                    )}

                    <PasswordConfirmDialog
                        trigger={
                            <Button
                                variant="outline"
                                data-test="sign-out-other-sessions"
                            >
                                {t('Sign out other sessions')}
                            </Button>
                        }
                        title={t('Sign out other sessions')}
                        description={t(
                            'Enter your password to sign out of every other browser and device, including ones that chose “Remember me”.',
                        )}
                        form={destroy.form()}
                        submitLabel={t('Sign out other sessions')}
                        submitTest="confirm-sign-out-other-sessions"
                        passwordId="session_password"
                    />
                </SettingsSection>
            </SettingsPage>
        </>
    );
}
