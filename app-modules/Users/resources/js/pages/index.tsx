import { Head, Link, router, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Download, MailPlus } from 'lucide-react';
import {
    DataTable,
    DataTableBulkDelete,
    DataTableCreateButton,
    DataTableDeleteDialog,
    DataTableFacetedFilter,
    DataTableToolbar,
} from '@/components/data-table';
import { Button } from '@/components/ui/button';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { useFormatters } from '@/hooks/use-formatters';
import { useResourceTable } from '@/hooks/use-resource-table';
import {
    bulkDestroy,
    create,
    destroy,
    exportMethod,
    impersonate,
    index,
} from '@/routes/admin/users';
import { index as invitationsIndex } from '@/routes/admin/users/invitations';
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
    { label: 'Not verified', value: 'no' },
];

export default function UsersIndex({ users, roles, can }: UsersIndexProps) {
    const { t, tChoice } = useLaravelReactI18n();
    const format = useFormatters();
    const { tableState, deleteDialog, selection, bulkDelete } =
        useResourceTable({
            key: 'users',
            rows: users.data,
            destroyUrl: (row) => destroy.url(row.id),
            bulkDestroyUrl: bulkDestroy.url(),
        });

    const pageUrl = usePage().url;
    const queryIndex = pageUrl.indexOf('?');
    const exportHref =
        exportMethod.url() +
        (queryIndex === -1 ? '' : pageUrl.slice(queryIndex));

    useBreadcrumbs({ title: t('Users'), href: index() });

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
                            <DataTableCreateButton
                                href={create()}
                                label={t('Create user')}
                            />
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
            <DataTableBulkDelete
                selection={selection}
                bulkDelete={bulkDelete}
                title={t('Delete selected users')}
                description={(count) =>
                    tChoice(
                        'This will permanently delete :count user and cannot be undone.|This will permanently delete :count users and cannot be undone.',
                        count,
                    )
                }
            />
            <DataTable
                selection={selection}
                columns={columns}
                paginated={users}
                tableState={tableState}
                emptyMessage={t('No users found.')}
            />
            <DataTableDeleteDialog
                dialog={deleteDialog}
                title={t('Delete user')}
                name={(user) => user.name}
                fallbackName={t('this user')}
            />
        </>
    );
}
