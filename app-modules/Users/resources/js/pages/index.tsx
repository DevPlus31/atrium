import { Head, Link, router, usePage } from '@inertiajs/react';
import { Download, Plus } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import {
    DataTable,
    DataTableFacetedFilter,
    DataTableToolbar,
} from '@/components/data-table';
import { Button } from '@/components/ui/button';
import { useTableState } from '@/hooks/use-table-state';
import AdminLayout from '@/layouts/admin-layout';
import { create, destroy, exportMethod, index } from '@/routes/admin/users';
import type { BreadcrumbItem } from '@/types';
import type { Paginated } from '@/types/admin';
import type { UserRow } from '../components/user-columns';
import { buildUserColumns } from '../components/user-columns';

type UsersIndexProps = {
    users: Paginated<UserRow>;
    roles: string[];
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Users', href: index() }];

const verifiedOptions = [
    { label: 'Verified', value: 'yes' },
    { label: 'Unverified', value: 'no' },
];

export default function UsersIndex({ users, roles }: UsersIndexProps) {
    const tableState = useTableState('users');
    const [pendingDelete, setPendingDelete] = useState<UserRow | null>(null);
    const [deleting, setDeleting] = useState(false);

    const pageUrl = usePage().url;
    const queryIndex = pageUrl.indexOf('?');
    const exportHref =
        exportMethod.url() +
        (queryIndex === -1 ? '' : pageUrl.slice(queryIndex));

    const columns = buildUserColumns(setPendingDelete);

    const confirmDelete = () => {
        if (pendingDelete === null) {
            return;
        }

        router.delete(destroy.url(pendingDelete.id), {
            preserveScroll: true,
            onStart: () => setDeleting(true),
            onFinish: () => {
                setDeleting(false);
                setPendingDelete(null);
            },
        });
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Users" />
            <DataTableToolbar
                tableState={tableState}
                searchPlaceholder="Search users..."
                actions={
                    <>
                        <Button variant="outline" size="sm" asChild>
                            <a href={exportHref}>
                                <Download className="size-4" />
                                Export
                            </a>
                        </Button>
                        <Button size="sm" asChild>
                            <Link href={create()}>
                                <Plus className="size-4" />
                                Create user
                            </Link>
                        </Button>
                    </>
                }
            >
                <DataTableFacetedFilter
                    tableState={tableState}
                    field="role"
                    title="Role"
                    options={roles.map((role) => ({
                        label: role,
                        value: role,
                    }))}
                />
                <DataTableFacetedFilter
                    tableState={tableState}
                    field="verified"
                    title="Verified"
                    options={verifiedOptions}
                />
            </DataTableToolbar>
            <DataTable
                columns={columns}
                paginated={users}
                tableState={tableState}
                emptyMessage="No users found."
            />
            <ConfirmDialog
                open={pendingDelete !== null}
                onOpenChange={(open) => {
                    if (!open && !deleting) {
                        setPendingDelete(null);
                    }
                }}
                title="Delete user"
                description={`This will permanently delete ${pendingDelete?.name ?? 'this user'} and cannot be undone.`}
                confirmLabel="Delete"
                processing={deleting}
                onConfirm={confirmDelete}
            />
        </AdminLayout>
    );
}
