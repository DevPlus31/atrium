import { Head, Link, router, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
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
import {
    create,
    destroy,
    exportMethod,
    impersonate,
    index,
} from '@/routes/admin/users';
import type { BreadcrumbItem } from '@/types';
import type { Paginated } from '@/types/admin';
import type { UserRow } from '../components/user-columns';
import { buildUserColumns } from '../components/user-columns';

type UsersIndexProps = {
    users: Paginated<UserRow>;
    roles: string[];
};

const verifiedOptions = [
    { label: 'Verified', value: 'yes' },
    { label: 'Unverified', value: 'no' },
];

export default function UsersIndex({ users, roles }: UsersIndexProps) {
    const { t } = useLaravelReactI18n();
    const tableState = useTableState('users');
    const [pendingDelete, setPendingDelete] = useState<UserRow | null>(null);
    const [deleting, setDeleting] = useState(false);

    const pageUrl = usePage().url;
    const queryIndex = pageUrl.indexOf('?');
    const exportHref =
        exportMethod.url() +
        (queryIndex === -1 ? '' : pageUrl.slice(queryIndex));

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('Users'), href: index() },
    ];

    const columns = buildUserColumns(t, setPendingDelete, (user) => {
        router.post(impersonate.url(user.id));
    });

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
            <Head title={t('Users')} />
            <DataTableToolbar
                tableState={tableState}
                searchPlaceholder={t('Search users...')}
                actions={
                    <>
                        <Button variant="outline" size="sm" asChild>
                            <a href={exportHref}>
                                <Download className="size-4" />
                                {t('Export')}
                            </a>
                        </Button>
                        <Button size="sm" asChild>
                            <Link href={create()}>
                                <Plus className="size-4" />
                                {t('Create user')}
                            </Link>
                        </Button>
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
            <DataTable
                columns={columns}
                paginated={users}
                tableState={tableState}
                emptyMessage={t('No users found.')}
            />
            <ConfirmDialog
                open={pendingDelete !== null}
                onOpenChange={(open) => {
                    if (!open && !deleting) {
                        setPendingDelete(null);
                    }
                }}
                title={t('Delete user')}
                description={t(
                    'This will permanently delete :name and cannot be undone.',
                    { name: pendingDelete?.name ?? t('this user') },
                )}
                confirmLabel={t('Delete')}
                processing={deleting}
                onConfirm={confirmDelete}
            />
        </AdminLayout>
    );
}
