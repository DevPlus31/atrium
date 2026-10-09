import { BadgeList } from '@/components/badge-list';
import {
    actionsColumn,
    dateColumn,
    sortableColumn,
    DeleteMenuItem,
    EditMenuItem,
    type DataTableColumn,
} from '@/components/data-table';
import { Badge } from '@/components/ui/badge';
import type { Formatters } from '@/lib/format';
import { edit } from '@/routes/admin/roles';
import type { Translator } from '@/types/ui';

export type RoleRow = Modules.Roles.Data.RoleData;

const VISIBLE_PERMISSIONS = 3;

export function buildRoleColumns(
    t: Translator,
    format: Formatters,
    onDelete: (role: RoleRow) => void,
): DataTableColumn<RoleRow>[] {
    return [
        sortableColumn<RoleRow>({
            id: 'name',
            title: t('Name'),
            cell: (row) => (
                <span className="flex items-center gap-2">
                    <span className="font-medium">{row.name}</span>
                    {row.is_system && (
                        <Badge variant="outline">{t('System')}</Badge>
                    )}
                </span>
            ),
        }),
        {
            id: 'permissions',
            enableSorting: false,
            header: t('Permissions'),
            cell: ({ row }) => (
                <BadgeList
                    items={row.original.permissions}
                    max={VISIBLE_PERMISSIONS}
                />
            ),
        },
        {
            id: 'users_count',
            enableSorting: false,
            header: t('Users'),
            cell: ({ row }) => <span>{row.original.users_count}</span>,
        },
        dateColumn<RoleRow>({
            id: 'created_at',
            title: t('Created'),
            value: (role) => role.created_at,
            format: format.date,
        }),
        actionsColumn<RoleRow>({
            label: t('Actions'),
            visible: (role) => role.can.update || role.can.delete,
            items: (role) => (
                <>
                    {role.can.update && (
                        <EditMenuItem href={edit(Number(role.id))} />
                    )}
                    {role.can.delete && (
                        <DeleteMenuItem onSelect={() => onDelete(role)} />
                    )}
                </>
            ),
        }),
    ];
}
