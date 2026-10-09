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
import { edit, index, update } from '@/routes/admin/products';
import type { BreadcrumbItem } from '@/types';
import type { ProductRow } from '../components/product-columns';
import { ProductFormFields } from '../components/product-form-fields';
import type { ProductFormData } from '../components/product-form-fields';

type ProductsEditProps = {
    product: ProductRow;
};

export default function ProductsEdit({ product }: ProductsEditProps) {
    const { t } = useLaravelReactI18n();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('Products'), href: index() },
        { title: product.name, href: edit(product.id) },
    ];
    useBreadcrumbs(breadcrumbs);

    const form = useForm<ProductFormData>(update(product.id), {
        name: product.name,
        sku: product.sku,
        price_cents: product.price_cents,
        currency: product.currency,
        description: product.description ?? '',
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.submit({ preserveScroll: true });
    };

    return (
        <>
            <Head title={t('Edit :name', { name: product.name })} />
            <Card className="max-w-2xl">
                <CardHeader>
                    <CardTitle>{t('Edit product')}</CardTitle>
                    <CardDescription>
                        {t('Update the product details.')}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="grid gap-6">
                        <ProductFormFields
                            data={form.data}
                            errors={form.errors}
                            setData={form.setData}
                            validate={(field) => form.validate(field)}
                        />

                        <div className="flex items-center gap-2">
                            <Button type="submit" disabled={form.processing}>
                                {form.processing && <Spinner />}
                                {t('Save changes')}
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
