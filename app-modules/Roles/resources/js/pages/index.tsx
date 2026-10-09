import { Head, Link } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Plus } from 'lucide-react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTable, DataTableToolbar } from '@/components/data-table';
import { Button } from '@/components/ui/button';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { useDeleteDialog } from '@/hooks/use-delete-dialog';
import { useFormatters } from '@/hooks/use-formatters';
import { useTableState } from '@/hooks/use-table-state';
import { create, destroy, index } from '@/routes/admin/roles';
import type { BreadcrumbItem } from '@/types';
import type { Paginated } from '@/types/admin';
import type { RoleRow } from '../components/role-columns';
import { buildRoleColumns } from '../components/role-columns';

type RolesIndexProps = {
    roles: Paginated<RoleRow>;
    can: { create: boolean };
};

export default function RolesIndex({ roles, can }: RolesIndexProps) {
    const { t } = useLaravelReactI18n();
    const format = useFormatters();
    const tableState = useTableState('roles');
    const deleteDialog = useDeleteDialog<RoleRow>((row) =>
        destroy.url(Number(row.id)),
    );

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('Roles'), href: index() },
    ];
    useBreadcrumbs(breadcrumbs);

    const columns = buildRoleColumns(t, format, deleteDialog.request);

    return (
        <>
            <Head title={t('Roles')} />
            <DataTableToolbar
                tableState={tableState}
                searchPlaceholder={t('Search roles...')}
                actions={
                    can.create && (
                        <Button size="sm" asChild>
                            <Link href={create()}>
                                <Plus className="size-4" />
                                {t('Create role')}
                            </Link>
                        </Button>
                    )
                }
            />
            <DataTable
                columns={columns}
                paginated={roles}
                tableState={tableState}
                emptyMessage={t('No roles found.')}
            />
            <ConfirmDialog
                {...deleteDialog.dialogProps}
                title={t('Delete role')}
                description={t(
                    'This will permanently delete :name and cannot be undone.',
                    { name: deleteDialog.pending?.name ?? t('this role') },
                )}
                confirmLabel={t('Delete')}
            />
        </>
    );
}
