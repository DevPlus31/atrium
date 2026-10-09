import { Head, useForm } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { ImageIcon } from 'lucide-react';
import { CheckboxField } from '@/components/checkbox-field';
import { FormCard } from '@/components/form-card';
import { ImageInput } from '@/components/image-input';
import { PageStack, SectionCard } from '@/components/section-card';
import { TextField } from '@/components/text-field';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { edit, update } from '@/routes/admin/settings/general';
import {
    destroy as removeLogo,
    update as uploadLogo,
} from '@/routes/admin/settings/logo';

type GeneralSettingsProps = {
    settings: Modules.Settings.Data.GeneralSettingsData;
};

export default function GeneralSettings({ settings }: GeneralSettingsProps) {
    const { t } = useLaravelReactI18n();

    useBreadcrumbs({ title: t('General settings'), href: edit() });

    const form = useForm(update(), {
        support_email: settings.support_email ?? '',
        registration_open: settings.registration_open,
    });

    return (
        <>
            <Head title={t('General settings')} />
            <PageStack>
                <SectionCard
                    title={t('Logo')}
                    description={t(
                        'Shown in the menu, on the sign-in pages and on error pages instead of the built-in mark.',
                    )}
                >
                    <ImageInput
                        label={t('Logo')}
                        shape="square"
                        imageUrl={settings.logo}
                        fallback={
                            <ImageIcon className="size-6" aria-hidden="true" />
                        }
                        field="logo"
                        uploadUrl={uploadLogo.url()}
                        removeUrl={removeLogo.url()}
                    />
                </SectionCard>

                <FormCard
                    title={t('General settings')}
                    description={t('Settings that apply to the whole app.')}
                    onSubmit={() => form.submit({ preserveScroll: true })}
                    processing={form.processing}
                    submitLabel={t('Save')}
                >
                    <TextField
                        form={form}
                        name="support_email"
                        type="email"
                        label={t('Support email')}
                        hint={t(
                            'Shown on error pages so people know who to ask. Leave empty to hide it.',
                        )}
                        autoComplete="off"
                        placeholder={t('support@example.com')}
                    />

                    <CheckboxField
                        id="registration_open"
                        label={t('Allow sign-ups')}
                        hint={t(
                            'When off, only administrators can create accounts.',
                        )}
                        error={form.errors.registration_open}
                        checked={form.data.registration_open}
                        onCheckedChange={(checked) =>
                            form.setData('registration_open', checked)
                        }
                    />
                </FormCard>
            </PageStack>
        </>
    );
}
