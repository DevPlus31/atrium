import { Head, useForm } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { FormCard } from '@/components/form-card';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { create, index, store } from '@/routes/admin/products';
import { ProductFormFields } from '../components/product-form-fields';
import type { ProductFormData } from '../components/product-form-fields';

export default function ProductsCreate() {
    const { t } = useLaravelReactI18n();

    useBreadcrumbs(
        { title: t('Products'), href: index() },
        { title: t('Create'), href: create() },
    );

    const form = useForm<ProductFormData>(store(), {
        name: '',
        sku: '',
        price_cents: 0,
        currency: 'USD',
        description: '',
    });

    return (
        <>
            <Head title={t('Create product')} />
            <FormCard
                title={t('Create product')}
                description={t('Add a new product to the catalog.')}
                onSubmit={() => form.submit({ preserveScroll: true })}
                processing={form.processing}
                submitLabel={t('Create product')}
                cancelHref={index()}
            >
                <ProductFormFields form={form} autoFocus />
            </FormCard>
        </>
    );
}
