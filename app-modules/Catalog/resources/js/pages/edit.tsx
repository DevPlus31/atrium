import { Head, useForm } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { FormCard } from '@/components/form-card';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { edit, index, update } from '@/routes/admin/products';
import type { ProductRow } from '../components/product-columns';
import { ProductFormFields } from '../components/product-form-fields';
import type { ProductFormData } from '../components/product-form-fields';

type ProductsEditProps = {
    product: ProductRow;
};

export default function ProductsEdit({ product }: ProductsEditProps) {
    const { t } = useLaravelReactI18n();

    useBreadcrumbs(
        { title: t('Products'), href: index() },
        { title: product.name, href: edit(product.id) },
    );

    const form = useForm<ProductFormData>(update(product.id), {
        name: product.name,
        sku: product.sku,
        price_cents: product.price_cents,
        currency: product.currency,
        description: product.description ?? '',
    });

    return (
        <>
            <Head title={t('Edit :name', { name: product.name })} />
            <FormCard
                title={t('Edit product')}
                description={t('Update the product details.')}
                onSubmit={() => form.submit({ preserveScroll: true })}
                processing={form.processing}
                submitLabel={t('Save changes')}
                cancelHref={index()}
            >
                <ProductFormFields form={form} />
            </FormCard>
        </>
    );
}
