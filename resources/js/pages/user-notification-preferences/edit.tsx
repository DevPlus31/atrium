import { Head, useForm } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import SettingsLayout from '@/layouts/settings/layout';
import { edit, update } from '@/routes/notification-preferences';
import type { BreadcrumbItem } from '@/types';

type NotificationPreferencesProps = {
    notifyByEmail: boolean;
};

export default function Edit({ notifyByEmail }: NotificationPreferencesProps) {
    const { t } = useLaravelReactI18n();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('Notification settings'), href: edit() },
    ];
    useBreadcrumbs(breadcrumbs);

    const form = useForm(update(), { notify_by_email: notifyByEmail });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.submit({ preserveScroll: true });
    };

    return (
        <>
            <Head title={t('Notification settings')} />

            <h1 className="sr-only">{t('Notification settings')}</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title={t('Notification settings')}
                        description={t(
                            'Notifications always appear under the bell. Choose whether they reach your inbox too.',
                        )}
                    />

                    <form onSubmit={submit} className="space-y-6">
                        <div className="flex items-start gap-3">
                            <Checkbox
                                id="notify_by_email"
                                checked={form.data.notify_by_email}
                                onCheckedChange={(checked) =>
                                    form.setData(
                                        'notify_by_email',
                                        checked === true,
                                    )
                                }
                            />
                            <div className="grid gap-1">
                                <Label htmlFor="notify_by_email">
                                    {t('Email me my notifications')}
                                </Label>
                                <p className="text-sm text-muted-foreground">
                                    {t(
                                        'Security emails (password resets, sign-in codes) are always sent.',
                                    )}
                                </p>
                                <InputError
                                    message={form.errors.notify_by_email}
                                />
                            </div>
                        </div>

                        <Button
                            type="submit"
                            disabled={form.processing}
                            data-test="save-notification-preferences"
                        >
                            {form.processing && <Spinner />}
                            {t('Save')}
                        </Button>
                    </form>
                </div>
            </SettingsLayout>
        </>
    );
}
