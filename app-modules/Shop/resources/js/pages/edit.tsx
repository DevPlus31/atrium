import { Head, Link, useForm } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { edit, index, update } from '@/routes/admin/orders';
import type { BreadcrumbItem } from '@/types';
import type { OrderRow } from '../components/order-columns';
import type { OrderFormData } from '../components/order-form-fields';
import { OrderFormFields } from '../components/order-form-fields';

type OrdersEditProps = {
    order: OrderRow;
};

export default function OrdersEdit({ order }: OrdersEditProps) {
    const { t } = useLaravelReactI18n();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('Orders'), href: index() },
        { title: order.number, href: edit(order.id) },
    ];
    useBreadcrumbs(breadcrumbs);

    const form = useForm<OrderFormData>(update(order.id), {
        customer_email: order.customer_email,
        total_cents: order.total_cents,
        currency: order.currency,
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.submit({ preserveScroll: true });
    };

    return (
        <>
            <Head title={t('Edit :number', { number: order.number })} />
            <Card className="max-w-2xl">
                <CardHeader>
                    <CardTitle>
                        {t('Edit :number', { number: order.number })}
                    </CardTitle>
                    <CardDescription>
                        {t(
                            'Pending orders can be changed until they are paid.',
                        )}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="grid gap-6">
                        <OrderFormFields
                            data={form.data}
                            errors={form.errors}
                            setData={form.setData}
                            validate={(field) => form.validate(field)}
                        />
                        <div className="flex items-center gap-2">
                            <Button type="submit" disabled={form.processing}>
                                {form.processing && <Spinner />}
                                {t('Save')}
                            </Button>
                            <Button variant="ghost" asChild>
                                <Link href={index()}>{t('Cancel')}</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </>
    );
}
