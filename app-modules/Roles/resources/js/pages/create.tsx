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
import AdminLayout from '@/layouts/admin-layout';
import { create, index, store } from '@/routes/admin/roles';
import type { BreadcrumbItem } from '@/types';
import { RolePermissionsField } from '../components/role-permissions-field';

type RolesCreateProps = {
    permissions: string[];
};

export default function RolesCreate({ permissions }: RolesCreateProps) {
    const { t } = useLaravelReactI18n();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('Roles'), href: index() },
        { title: t('Create'), href: create() },
    ];

    const form = useForm('post', store.url(), {
        name: '',
        permissions: [] as string[],
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.submit({ preserveScroll: true });
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={t('Create role')} />
            <Card className="max-w-2xl">
                <CardHeader>
                    <CardTitle>{t('Create role')}</CardTitle>
                    <CardDescription>
                        {t('Add a new role and choose its permissions.')}
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
                                autoFocus
                                autoComplete="off"
                                value={form.data.name}
                                onChange={(event) =>
                                    form.setData('name', event.target.value)
                                }
                                onBlur={() => form.validate('name')}
                                placeholder={t('Role name')}
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        <RolePermissionsField
                            permissions={permissions}
                            selected={form.data.permissions}
                            onChange={(next) => {
                                form.setData('permissions', next);
                                form.validate('permissions');
                            }}
                            error={form.errors.permissions}
                        />

                        <div className="flex items-center gap-2">
                            <Button type="submit" disabled={form.processing}>
                                {form.processing && <Spinner />}
                                {t('Create role')}
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
