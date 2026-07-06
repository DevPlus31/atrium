import { Head, Link, router } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
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

export default function RolesIndex({ roles }: RolesIndexProps) {
    const { t } = useLaravelReactI18n();
    const tableState = useTableState('roles');
    const [pendingDelete, setPendingDelete] = useState<RoleRow | null>(null);
    const [deleting, setDeleting] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('Roles'), href: index() },
    ];

    const columns = buildRoleColumns(t, setPendingDelete);

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
            <Head title={t('Roles')} />
            <DataTableToolbar
                tableState={tableState}
                searchPlaceholder={t('Search roles...')}
                actions={
                    <Button size="sm" asChild>
                        <Link href={create()}>
                            <Plus className="size-4" />
                            {t('Create role')}
                        </Link>
                    </Button>
                }
            />
            <DataTable
                columns={columns}
                paginated={roles}
                tableState={tableState}
                emptyMessage={t('No roles found.')}
            />
            <ConfirmDialog
                open={pendingDelete !== null}
                onOpenChange={(open) => {
                    if (!open && !deleting) {
                        setPendingDelete(null);
                    }
                }}
                title={t('Delete role')}
                description={t(
                    'This will permanently delete :name and cannot be undone.',
                    { name: pendingDelete?.name ?? t('this role') },
                )}
                confirmLabel={t('Delete')}
                processing={deleting}
                onConfirm={confirmDelete}
            />
        </AdminLayout>
    );
}
