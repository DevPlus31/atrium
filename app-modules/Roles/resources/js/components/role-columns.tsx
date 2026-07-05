import { Link } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import {
    DataTableColumnHeader,
    DataTableRowActions,
} from '@/components/data-table';
import { Badge } from '@/components/ui/badge';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { edit } from '@/routes/admin/roles';

export type RoleRow = Modules.Roles.Data.RoleData;

const VISIBLE_PERMISSIONS = 3;

function formatDate(value: string): string {
    return new Date(value).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

export function buildRoleColumns(
    onDelete: (role: RoleRow) => void,
): ColumnDef<RoleRow, unknown>[] {
    return [
        {
            id: 'name',
            accessorKey: 'name',
            enableSorting: true,
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title="Name" />
            ),
            cell: ({ row }) => (
                <span className="flex items-center gap-2">
                    <span className="font-medium">{row.original.name}</span>
                    {row.original.is_system && (
                        <Badge variant="outline">System</Badge>
                    )}
                </span>
            ),
        },
        {
            id: 'permissions',
            enableSorting: false,
            header: 'Permissions',
            cell: ({ row }) => {
                const permissions = row.original.permissions;

                if (permissions.length === 0) {
                    return <span className="text-muted-foreground">—</span>;
                }

                const visible = permissions.slice(0, VISIBLE_PERMISSIONS);
                const remaining = permissions.length - visible.length;

                return (
                    <div className="flex flex-wrap items-center gap-1">
                        {visible.map((permission) => (
                            <Badge key={permission} variant="secondary">
                                {permission}
                            </Badge>
                        ))}
                        {remaining > 0 && (
                            <span className="text-xs text-muted-foreground">
                                +{remaining} more
                            </span>
                        )}
                    </div>
                );
            },
        },
        {
            id: 'users_count',
            enableSorting: false,
            header: 'Users',
            cell: ({ row }) => <span>{row.original.users_count}</span>,
        },
        {
            id: 'created_at',
            accessorKey: 'created_at',
            enableSorting: true,
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title="Created" />
            ),
            cell: ({ row }) => (
                <span className="text-muted-foreground">
                    {formatDate(row.original.created_at)}
                </span>
            ),
        },
        {
            id: 'actions',
            enableSorting: false,
            header: () => <span className="sr-only">Actions</span>,
            cell: ({ row }) => {
                const role = row.original;

                if (!role.can.update && !role.can.delete) {
                    return null;
                }

                return (
                    <div className="flex justify-end">
                        <DataTableRowActions row={row}>
                            {() => (
                                <>
                                    {role.can.update && (
                                        <DropdownMenuItem asChild>
                                            <Link href={edit(Number(role.id))}>
                                                Edit
                                            </Link>
                                        </DropdownMenuItem>
                                    )}
                                    {role.can.delete && (
                                        <DropdownMenuItem
                                            variant="destructive"
                                            onSelect={() => onDelete(role)}
                                        >
                                            Delete
                                        </DropdownMenuItem>
                                    )}
                                </>
                            )}
                        </DataTableRowActions>
                    </div>
                );
            },
        },
    ];
}
