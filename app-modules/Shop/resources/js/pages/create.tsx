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
import { create, index, store } from '@/routes/admin/orders';
import type { BreadcrumbItem } from '@/types';
import type { OrderFormData } from '../components/order-form-fields';
import { OrderFormFields } from '../components/order-form-fields';

export default function OrdersCreate({
    defaultCurrency,
}: {
    defaultCurrency: string;
}) {
    const { t } = useLaravelReactI18n();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('Orders'), href: index() },
        { title: t('Create'), href: create() },
    ];
    useBreadcrumbs(breadcrumbs);

    const form = useForm<OrderFormData>(store(), {
        customer_email: '',
        total_cents: 0,
        currency: defaultCurrency,
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.submit({ preserveScroll: true });
    };

    return (
        <>
            <Head title={t('Create order')} />
            <Card className="max-w-2xl">
                <CardHeader>
                    <CardTitle>{t('Create order')}</CardTitle>
                    <CardDescription>
                        {t('Place a pending order for a customer.')}
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
                                {t('Create order')}
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
