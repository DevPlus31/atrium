import { Head, Link } from '@inertiajs/react';
import { useForm } from 'laravel-precognition-react-inertia';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
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
import { create, index, store } from '@/routes/admin/users';
import type { BreadcrumbItem } from '@/types';
import { UserRolesField } from '../components/user-roles-field';

type UsersCreateProps = {
    roles: string[];
};

export default function UsersCreate({ roles }: UsersCreateProps) {
    const { t } = useLaravelReactI18n();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('Users'), href: index() },
        { title: t('Create'), href: create() },
    ];

    const form = useForm('post', store.url(), {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        roles: [] as string[],
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.submit({ preserveScroll: true });
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={t('Create user')} />
            <Card className="max-w-2xl">
                <CardHeader>
                    <CardTitle>{t('Create user')}</CardTitle>
                    <CardDescription>
                        {t('Add a new user and assign their roles.')}
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
                                placeholder={t('Full name')}
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">{t('Email address')}</Label>
                            <Input
                                id="email"
                                type="email"
                                required
                                autoComplete="off"
                                value={form.data.email}
                                onChange={(event) =>
                                    form.setData('email', event.target.value)
                                }
                                onBlur={() => form.validate('email')}
                                placeholder="email@example.com"
                            />
                            <InputError message={form.errors.email} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">{t('Password')}</Label>
                            <PasswordInput
                                id="password"
                                required
                                autoComplete="new-password"
                                value={form.data.password}
                                onChange={(event) =>
                                    form.setData('password', event.target.value)
                                }
                                onBlur={() => form.validate('password')}
                                placeholder={t('Password')}
                            />
                            <InputError message={form.errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">
                                {t('Confirm password')}
                            </Label>
                            <PasswordInput
                                id="password_confirmation"
                                required
                                autoComplete="new-password"
                                value={form.data.password_confirmation}
                                onChange={(event) =>
                                    form.setData(
                                        'password_confirmation',
                                        event.target.value,
                                    )
                                }
                                onBlur={() => form.validate('password')}
                                placeholder={t('Confirm password')}
                            />
                            <InputError
                                message={form.errors.password_confirmation}
                            />
                        </div>

                        <UserRolesField
                            roles={roles}
                            selected={form.data.roles}
                            onChange={(next) => {
                                form.setData('roles', next);
                                form.validate('roles');
                            }}
                            error={form.errors.roles}
                        />

                        <div className="flex items-center gap-2">
                            <Button type="submit" disabled={form.processing}>
                                {form.processing && <Spinner />}
                                {t('Create user')}
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
