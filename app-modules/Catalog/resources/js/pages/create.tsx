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
import { create, index, store } from '@/routes/admin/products';
import type { BreadcrumbItem } from '@/types';
import { ProductFormFields } from '../components/product-form-fields';
import type { ProductFormData } from '../components/product-form-fields';

export default function ProductsCreate() {
    const { t } = useLaravelReactI18n();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('Products'), href: index() },
        { title: t('Create'), href: create() },
    ];
    useBreadcrumbs(breadcrumbs);

    const form = useForm<ProductFormData>(store(), {
        name: '',
        sku: '',
        price_cents: 0,
        currency: 'USD',
        description: '',
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.submit({ preserveScroll: true });
    };

    return (
        <>
            <Head title={t('Create product')} />
            <Card className="max-w-2xl">
                <CardHeader>
                    <CardTitle>{t('Create product')}</CardTitle>
                    <CardDescription>
                        {t('Add a new product to the catalog.')}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="grid gap-6">
                        <ProductFormFields
                            data={form.data}
                            errors={form.errors}
                            setData={form.setData}
                            validate={(field) => form.validate(field)}
                            autoFocus
                        />

                        <div className="flex items-center gap-2">
                            <Button type="submit" disabled={form.processing}>
                                {form.processing && <Spinner />}
                                {t('Create product')}
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
