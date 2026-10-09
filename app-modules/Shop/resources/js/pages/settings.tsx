import { Head, useForm } from '@inertiajs/react';
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
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { edit, update } from '@/routes/admin/shop/settings';
import type { BreadcrumbItem } from '@/types';

export default function ShopSettings({
    defaultCurrency,
}: {
    defaultCurrency: string;
}) {
    const { t } = useLaravelReactI18n();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('Shop settings'), href: edit() },
    ];
    useBreadcrumbs(breadcrumbs);

    const form = useForm(update(), { default_currency: defaultCurrency });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.submit({ preserveScroll: true });
    };

    return (
        <>
            <Head title={t('Shop settings')} />
            <Card className="max-w-2xl">
                <CardHeader>
                    <CardTitle>{t('Shop settings')}</CardTitle>
                    <CardDescription>
                        {t('Defaults for new orders.')}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="grid gap-6">
                        <div className="grid gap-2">
                            <Label htmlFor="default_currency">
                                {t('Default currency')}
                            </Label>
                            <Input
                                id="default_currency"
                                className="w-32 uppercase"
                                maxLength={3}
                                autoComplete="off"
                                value={form.data.default_currency}
                                onChange={(event) =>
                                    form.setData(
                                        'default_currency',
                                        event.target.value.toUpperCase(),
                                    )
                                }
                                onBlur={() => form.validate('default_currency')}
                            />
                            <InputError
                                message={form.errors.default_currency}
                            />
                        </div>

                        <div>
                            <Button type="submit" disabled={form.processing}>
                                {form.processing && <Spinner />}
                                {t('Save')}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </>
    );
}
