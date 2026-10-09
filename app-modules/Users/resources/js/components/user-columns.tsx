import { BadgeCheck, CircleDashed } from 'lucide-react';
import { BadgeList } from '@/components/badge-list';
import {
    actionsColumn,
    dateColumn,
    sortableColumn,
    DeleteMenuItem,
    EditMenuItem,
    type DataTableColumn,
} from '@/components/data-table';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { UserInfo } from '@/components/user-info';
import type { Formatters } from '@/lib/format';
import { edit } from '@/routes/admin/users';
import type { Translator } from '@/types/ui';

export type UserRow = Modules.Users.Data.UserData;

export function buildUserColumns(
    t: Translator,
    format: Formatters,
    onDelete: (user: UserRow) => void,
    onImpersonate: (user: UserRow) => void,
): DataTableColumn<UserRow>[] {
    return [
        sortableColumn<UserRow>({
            id: 'name',
            title: t('Name'),
            cell: (row) => (
                <span className="flex items-center gap-3">
                    <UserInfo user={row} />
                </span>
            ),
        }),
        sortableColumn<UserRow>({ id: 'email', title: t('Email') }),
        {
            id: 'roles',
            enableSorting: false,
            header: t('Roles'),
            cell: ({ row }) => <BadgeList items={row.original.roles} />,
        },
        {
            id: 'verified',
            enableSorting: false,
            header: t('Verified'),
            cell: ({ row }) =>
                row.original.email_verified_at === null ? (
                    <span className="flex items-center gap-1.5 text-muted-foreground">
                        <CircleDashed className="size-4" />
                        {t('Not verified')}
                    </span>
                ) : (
                    <span className="flex items-center gap-1.5">
                        <BadgeCheck className="size-4" />
                        {t('Verified')}
                    </span>
                ),
        },
        dateColumn<UserRow>({
            id: 'created_at',
            title: t('Created'),
            value: (user) => user.created_at,
            format: format.date,
        }),
        actionsColumn<UserRow>({
            label: t('Actions'),
            visible: (user) =>
                user.can.update || user.can.delete || user.can.impersonate,
            items: (user) => (
                <>
                    {user.can.update && <EditMenuItem href={edit(user.id)} />}
                    {user.can.impersonate && (
                        <DropdownMenuItem onSelect={() => onImpersonate(user)}>
                            {t('Impersonate')}
                        </DropdownMenuItem>
                    )}
                    {user.can.delete && (
                        <DeleteMenuItem onSelect={() => onDelete(user)} />
                    )}
                </>
            ),
        }),
    ];
}
