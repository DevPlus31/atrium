import { Head, useForm } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { FormCard } from '@/components/form-card';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { edit, index, update } from '@/routes/admin/orders';
import type { OrderRow } from '../components/order-columns';
import type { OrderFormData } from '../components/order-form-fields';
import { OrderFormFields } from '../components/order-form-fields';

type OrdersEditProps = {
    order: OrderRow;
};

export default function OrdersEdit({ order }: OrdersEditProps) {
    const { t } = useLaravelReactI18n();

    useBreadcrumbs(
        { title: t('Orders'), href: index() },
        { title: order.number, href: edit(order.id) },
    );

    const form = useForm<OrderFormData>(update(order.id), {
        customer_email: order.customer_email,
        total_cents: order.total_cents,
        currency: order.currency,
    });

    return (
        <>
            <Head title={t('Edit :number', { number: order.number })} />
            <FormCard
                title={t('Edit :number', { number: order.number })}
                description={t(
                    'Pending orders can be changed until they are paid.',
                )}
                onSubmit={() => form.submit({ preserveScroll: true })}
                processing={form.processing}
                submitLabel={t('Save changes')}
                cancelHref={index()}
            >
                <OrderFormFields form={form} />
            </FormCard>
        </>
    );
}
