import { Head, useForm } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { FormCard } from '@/components/form-card';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { create, index, store } from '@/routes/admin/orders';
import type { OrderFormData } from '../components/order-form-fields';
import { OrderFormFields } from '../components/order-form-fields';

export default function OrdersCreate({
    defaultCurrency,
}: {
    defaultCurrency: string;
}) {
    const { t } = useLaravelReactI18n();

    useBreadcrumbs(
        { title: t('Orders'), href: index() },
        { title: t('Create'), href: create() },
    );

    const form = useForm<OrderFormData>(store(), {
        customer_email: '',
        total_cents: 0,
        currency: defaultCurrency,
    });

    return (
        <>
            <Head title={t('Create order')} />
            <FormCard
                title={t('Create order')}
                description={t('Place a pending order for a customer.')}
                onSubmit={() => form.submit({ preserveScroll: true })}
                processing={form.processing}
                submitLabel={t('Create order')}
                cancelHref={index()}
            >
                <OrderFormFields form={form} />
            </FormCard>
        </>
    );
}
