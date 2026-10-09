import { Head, Link, router, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Download, MailPlus, Plus } from 'lucide-react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import {
    DataTable,
    DataTableBulkActions,
    DataTableFacetedFilter,
    DataTableToolbar,
} from '@/components/data-table';
import { Button } from '@/components/ui/button';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { useBulkDelete } from '@/hooks/use-bulk-delete';
import { useDeleteDialog } from '@/hooks/use-delete-dialog';
import { useFormatters } from '@/hooks/use-formatters';
import { useRowSelection } from '@/hooks/use-row-selection';
import { useTableState } from '@/hooks/use-table-state';
import {
    bulkDestroy,
    create,
    destroy,
    exportMethod,
    impersonate,
    index,
} from '@/routes/admin/users';
import { index as invitationsIndex } from '@/routes/admin/users/invitations';
import type { BreadcrumbItem } from '@/types';
import type { Paginated } from '@/types/admin';
import type { UserRow } from '../components/user-columns';
import { buildUserColumns } from '../components/user-columns';

type UsersIndexProps = {
    users: Paginated<UserRow>;
    roles: string[];
    can: { create: boolean; export: boolean };
};

const verifiedOptions = [
    { label: 'Verified', value: 'yes' },
    { label: 'Unverified', value: 'no' },
];

export default function UsersIndex({ users, roles, can }: UsersIndexProps) {
    const { t, tChoice } = useLaravelReactI18n();
    const format = useFormatters();
    const tableState = useTableState('users');
    const deleteDialog = useDeleteDialog<UserRow>((row) => destroy.url(row.id));
    const selection = useRowSelection(
        users.data,
        (row) => row.id,
        (row) => row.can.delete,
    );
    const bulkDelete = useBulkDelete(bulkDestroy.url(), selection);

    const pageUrl = usePage().url;
    const queryIndex = pageUrl.indexOf('?');
    const exportHref =
        exportMethod.url() +
        (queryIndex === -1 ? '' : pageUrl.slice(queryIndex));

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('Users'), href: index() },
    ];
    useBreadcrumbs(breadcrumbs);

    const columns = buildUserColumns(
        t,
        format,
        deleteDialog.request,
        (user) => {
            router.post(impersonate.url(user.id));
        },
    );

    return (
        <>
            <Head title={t('Users')} />
            <DataTableToolbar
                tableState={tableState}
                searchPlaceholder={t('Search users...')}
                actions={
                    <>
                        {can.export && (
                            <Button variant="outline" size="sm" asChild>
                                <a href={exportHref}>
                                    <Download className="size-4" />
                                    {t('Export')}
                                </a>
                            </Button>
                        )}
                        {can.create && (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={invitationsIndex()}>
                                    <MailPlus className="size-4" />
                                    {t('Invitations')}
                                </Link>
                            </Button>
                        )}
                        {can.create && (
                            <Button size="sm" asChild>
                                <Link href={create()}>
                                    <Plus className="size-4" />
                                    {t('Create user')}
                                </Link>
                            </Button>
                        )}
                    </>
                }
            >
                <DataTableFacetedFilter
                    tableState={tableState}
                    field="role"
                    title={t('Role')}
                    options={roles.map((role) => ({
                        label: role,
                        value: role,
                    }))}
                />
                <DataTableFacetedFilter
                    tableState={tableState}
                    field="verified"
                    title={t('Verified')}
                    options={verifiedOptions.map((option) => ({
                        label: t(option.label),
                        value: option.value,
                    }))}
                />
            </DataTableToolbar>
            <DataTableBulkActions selection={selection}>
                <Button
                    variant="destructive"
                    size="sm"
                    onClick={bulkDelete.request}
                    data-test="bulk-delete"
                >
                    {t('Delete selected')}
                </Button>
            </DataTableBulkActions>
            <DataTable
                selection={selection}
                columns={columns}
                paginated={users}
                tableState={tableState}
                emptyMessage={t('No users found.')}
            />
            <ConfirmDialog
                {...bulkDelete.dialogProps}
                title={t('Delete selected users')}
                description={tChoice(
                    'This will permanently delete :count user and cannot be undone.|This will permanently delete :count users and cannot be undone.',
                    selection.selectedIds.length,
                )}
                confirmLabel={t('Delete')}
            />
            <ConfirmDialog
                {...deleteDialog.dialogProps}
                title={t('Delete user')}
                description={t(
                    'This will permanently delete :name and cannot be undone.',
                    { name: deleteDialog.pending?.name ?? t('this user') },
                )}
                confirmLabel={t('Delete')}
            />
        </>
    );
}
