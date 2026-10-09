import { useForm } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { CheckboxField } from '@/components/checkbox-field';
import { SettingsSection } from '@/components/settings-section';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { SettingsPage } from '@/layouts/settings/page';
import { handleSubmit } from '@/lib/utils';
import { edit, update } from '@/routes/notification-preferences';

type NotificationPreferencesProps = {
    notifyByEmail: boolean;
};

export default function Edit({ notifyByEmail }: NotificationPreferencesProps) {
    const { t } = useLaravelReactI18n();

    const form = useForm(update(), { notify_by_email: notifyByEmail });

    return (
        <>
            <SettingsPage title={t('Notification settings')} href={edit()}>
                <SettingsSection
                    title={t('Notification settings')}
                    description={t(
                        'Notifications always appear under the bell. Choose whether they reach your inbox too.',
                    )}
                >
                    <form
                        onSubmit={handleSubmit(() =>
                            form.submit({ preserveScroll: true }),
                        )}
                        className="space-y-6"
                    >
                        <CheckboxField
                            id="notify_by_email"
                            label={t('Email me my notifications')}
                            hint={t(
                                'Security emails (password resets, sign-in codes) are always sent.',
                            )}
                            error={form.errors.notify_by_email}
                            checked={form.data.notify_by_email}
                            onCheckedChange={(checked) =>
                                form.setData('notify_by_email', checked)
                            }
                        />

                        <Button
                            type="submit"
                            disabled={form.processing}
                            data-test="save-notification-preferences"
                        >
                            {form.processing && <Spinner />}
                            {t('Save')}
                        </Button>
                    </form>
                </SettingsSection>
            </SettingsPage>
        </>
    );
}
