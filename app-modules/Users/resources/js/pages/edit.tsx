import { Head, Link } from '@inertiajs/react';
import { useForm } from 'laravel-precognition-react-inertia';
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
import { edit, index, update } from '@/routes/admin/users';
import type { BreadcrumbItem } from '@/types';
import type { UserRow } from '../components/user-columns';
import { UserRolesField } from '../components/user-roles-field';

type UsersEditProps = {
    user: UserRow;
    roles: string[];
};

export default function UsersEdit({ user, roles }: UsersEditProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Users', href: index() },
        { title: user.name, href: edit(user.id) },
    ];

    const form = useForm('put', update.url(user.id), {
        name: user.name,
        email: user.email,
        roles: user.roles,
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.submit({ preserveScroll: true });
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${user.name}`} />
            <Card className="max-w-2xl">
                <CardHeader>
                    <CardTitle>Edit user</CardTitle>
                    <CardDescription>
                        Update the user's details and roles. Changing the email
                        address resets its verification.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="grid gap-6">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>
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
                                placeholder="Full name"
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">Email address</Label>
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
                                Save changes
                            </Button>
                            <Button variant="ghost" asChild>
                                <Link href={index()}>Cancel</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
