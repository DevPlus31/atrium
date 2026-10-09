import { Head, useForm } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { FormCard } from '@/components/form-card';
import { TextField } from '@/components/text-field';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { edit, update } from '@/routes/admin/shop/settings';

export default function ShopSettings({
    defaultCurrency,
}: {
    defaultCurrency: string;
}) {
    const { t } = useLaravelReactI18n();

    useBreadcrumbs({ title: t('Shop settings'), href: edit() });

    const form = useForm(update(), { default_currency: defaultCurrency });

    return (
        <>
            <Head title={t('Shop settings')} />
            <FormCard
                title={t('Shop settings')}
                description={t('Defaults for new orders.')}
                onSubmit={() => form.submit({ preserveScroll: true })}
                processing={form.processing}
                submitLabel={t('Save')}
            >
                <TextField
                    form={form}
                    name="default_currency"
                    type="currency"
                    label={t('Default currency')}
                    className="w-32"
                />
            </FormCard>
        </>
    );
}
