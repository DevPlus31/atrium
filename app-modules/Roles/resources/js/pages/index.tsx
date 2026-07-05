import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTable, DataTableToolbar } from '@/components/data-table';
import { Button } from '@/components/ui/button';
import { useTableState } from '@/hooks/use-table-state';
import AdminLayout from '@/layouts/admin-layout';
import { create, destroy, index } from '@/routes/admin/roles';
import type { BreadcrumbItem } from '@/types';
import type { Paginated } from '@/types/admin';
import type { RoleRow } from '../components/role-columns';
import { buildRoleColumns } from '../components/role-columns';

type RolesIndexProps = {
    roles: Paginated<RoleRow>;
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Roles', href: index() }];

export default function RolesIndex({ roles }: RolesIndexProps) {
    const tableState = useTableState('roles');
    const [pendingDelete, setPendingDelete] = useState<RoleRow | null>(null);
    const [deleting, setDeleting] = useState(false);

    const columns = buildRoleColumns(setPendingDelete);

    const confirmDelete = () => {
        if (pendingDelete === null) {
            return;
        }

        router.delete(destroy.url(Number(pendingDelete.id)), {
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
            <Head title="Roles" />
            <DataTableToolbar
                tableState={tableState}
                searchPlaceholder="Search roles..."
                actions={
                    <Button size="sm" asChild>
                        <Link href={create()}>
                            <Plus className="size-4" />
                            Create role
                        </Link>
                    </Button>
                }
            />
            <DataTable
                columns={columns}
                paginated={roles}
                tableState={tableState}
                emptyMessage="No roles found."
            />
            <ConfirmDialog
                open={pendingDelete !== null}
                onOpenChange={(open) => {
                    if (!open && !deleting) {
                        setPendingDelete(null);
                    }
                }}
                title="Delete role"
                description={`This will permanently delete ${pendingDelete?.name ?? 'this role'} and cannot be undone.`}
                confirmLabel="Delete"
                processing={deleting}
                onConfirm={confirmDelete}
            />
        </AdminLayout>
    );
}
