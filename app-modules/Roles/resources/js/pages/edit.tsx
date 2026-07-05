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
import { edit, index, update } from '@/routes/admin/roles';
import type { BreadcrumbItem } from '@/types';
import type { RoleRow } from '../components/role-columns';
import { RolePermissionsField } from '../components/role-permissions-field';

type RolesEditProps = {
    role: RoleRow;
    permissions: string[];
};

export default function RolesEdit({ role, permissions }: RolesEditProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Roles', href: index() },
        { title: role.name, href: edit(Number(role.id)) },
    ];

    const form = useForm('put', update.url(Number(role.id)), {
        name: role.name,
        permissions: role.permissions,
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.submit({ preserveScroll: true });
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${role.name}`} />
            <Card className="max-w-2xl">
                <CardHeader>
                    <CardTitle>Edit role</CardTitle>
                    <CardDescription>
                        Update the role's name and permissions.
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
                                disabled={role.is_system}
                                value={form.data.name}
                                onChange={(event) =>
                                    form.setData('name', event.target.value)
                                }
                                onBlur={() => form.validate('name')}
                                placeholder="Role name"
                            />
                            {role.is_system && (
                                <p className="text-sm text-muted-foreground">
                                    System role names cannot be changed.
                                </p>
                            )}
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
