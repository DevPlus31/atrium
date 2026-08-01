import { Head, Link } from '@inertiajs/react';
import { useForm } from 'laravel-precognition-react-inertia';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import AdminLayout from '@/layouts/admin-layout';
import { edit, index, update } from '@/routes/admin/products';
import type { BreadcrumbItem } from '@/types';
import type { ProductRow } from '../components/product-columns';

type ProductsEditProps = {
    product: ProductRow;
};

export default function ProductsEdit({ product }: ProductsEditProps) {
    const { t } = useLaravelReactI18n();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('Products'), href: index() },
        { title: product.name, href: edit(product.id) },
    ];

    const form = useForm('put', update.url(product.id), {
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
        <AdminLayout breadcrumbs={breadcrumbs}>
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
                        <div className="grid gap-2">
                            <Label htmlFor="name">{t('Name')}</Label>
                            <Input
                                id="name"
                                type="text"
                                required
                                autoComplete="off"
                                value={form.data.name}
                                onChange={(event) =>
                                    form.setData('name', event.target.value)
                                }
                                onBlur={() => form.validate('name')}
                                placeholder={t('Product name')}
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="sku">{t('SKU')}</Label>
                                <Input
                                    id="sku"
                                    type="text"
                                    required
                                    autoComplete="off"
                                    value={form.data.sku}
                                    onChange={(event) =>
                                        form.setData('sku', event.target.value)
                                    }
                                    onBlur={() => form.validate('sku')}
                                    placeholder={t('e.g. WIDGET-001')}
                                />
                                <InputError message={form.errors.sku} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="currency">
                                    {t('Currency')}
                                </Label>
                                <Input
                                    id="currency"
                                    type="text"
                                    required
                                    maxLength={3}
                                    autoComplete="off"
                                    value={form.data.currency}
                                    onChange={(event) =>
                                        form.setData(
                                            'currency',
                                            event.target.value.toUpperCase(),
                                        )
                                    }
                                    onBlur={() => form.validate('currency')}
                                    placeholder="USD"
                                />
                                <InputError message={form.errors.currency} />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="price_cents">
                                {t('Price (in cents)')}
                            </Label>
                            <Input
                                id="price_cents"
                                type="number"
                                min={0}
                                required
                                value={form.data.price_cents}
                                onChange={(event) =>
                                    form.setData(
                                        'price_cents',
                                        Number(event.target.value),
                                    )
                                }
                                onBlur={() => form.validate('price_cents')}
                            />
                            <InputError message={form.errors.price_cents} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="description">
                                {t('Description')}
                            </Label>
                            <Textarea
                                id="description"
                                rows={4}
                                value={form.data.description}
                                onChange={(event) =>
                                    form.setData(
                                        'description',
                                        event.target.value,
                                    )
                                }
                                onBlur={() => form.validate('description')}
                                placeholder={t('Optional product description')}
                            />
                            <InputError message={form.errors.description} />
                        </div>

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
        </AdminLayout>
    );
}
