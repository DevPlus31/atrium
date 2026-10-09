import { Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import {
    DataTable,
    DataTableCreateButton,
    DataTableDeleteDialog,
    DataTableToolbar,
} from '@/components/data-table';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { useFormatters } from '@/hooks/use-formatters';
import { useResourceTable } from '@/hooks/use-resource-table';
import { create, destroy, index } from '@/routes/admin/roles';
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
    const { tableState, deleteDialog } = useResourceTable({
        key: 'roles',
        rows: roles.data,
        destroyUrl: (row) => destroy.url(Number(row.id)),
    });

    useBreadcrumbs({ title: t('Roles'), href: index() });

    const columns = buildRoleColumns(t, format, deleteDialog.request);

    return (
        <>
            <Head title={t('Roles')} />
            <DataTableToolbar
                tableState={tableState}
                searchPlaceholder={t('Search roles...')}
                actions={
                    can.create && (
                        <DataTableCreateButton
                            href={create()}
                            label={t('Create role')}
                        />
                    )
                }
            />
            <DataTable
                columns={columns}
                paginated={roles}
                tableState={tableState}
                emptyMessage={t('No roles found.')}
            />
            <DataTableDeleteDialog
                dialog={deleteDialog}
                title={t('Delete role')}
                name={(role) => role.name}
                fallbackName={t('this role')}
            />
        </>
    );
}
