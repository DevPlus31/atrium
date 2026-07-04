import { Link } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { BadgeCheck, CircleDashed } from 'lucide-react';
import {
    DataTableColumnHeader,
    DataTableRowActions,
} from '@/components/data-table';
import { Badge } from '@/components/ui/badge';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { edit } from '@/routes/admin/users';

export type UserRow = Modules.Users.Data.UserData;

function formatDate(value: string): string {
    return new Date(value).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

export function buildUserColumns(
    onDelete: (user: UserRow) => void,
): ColumnDef<UserRow, unknown>[] {
    return [
        {
            id: 'name',
            accessorKey: 'name',
            enableSorting: true,
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title="Name" />
            ),
            cell: ({ row }) => (
                <span className="font-medium">{row.original.name}</span>
            ),
        },
        {
            id: 'email',
            accessorKey: 'email',
            enableSorting: true,
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title="Email" />
            ),
        },
        {
            id: 'roles',
            enableSorting: false,
            header: 'Roles',
            cell: ({ row }) =>
                row.original.roles.length > 0 ? (
                    <div className="flex flex-wrap gap-1">
                        {row.original.roles.map((role) => (
                            <Badge key={role} variant="secondary">
                                {role}
                            </Badge>
                        ))}
                    </div>
                ) : (
                    <span className="text-muted-foreground">—</span>
                ),
        },
        {
            id: 'verified',
            enableSorting: false,
            header: 'Verified',
            cell: ({ row }) =>
                row.original.email_verified_at === null ? (
                    <span className="flex items-center gap-1.5 text-muted-foreground">
                        <CircleDashed className="size-4" />
                        Unverified
                    </span>
                ) : (
                    <span className="flex items-center gap-1.5">
                        <BadgeCheck className="size-4" />
                        Verified
                    </span>
                ),
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
                const user = row.original;

                if (!user.can.update && !user.can.delete) {
                    return null;
                }

                return (
                    <div className="flex justify-end">
                        <DataTableRowActions row={row}>
                            {() => (
                                <>
                                    {user.can.update && (
                                        <DropdownMenuItem asChild>
                                            <Link href={edit(user.id)}>
                                                Edit
                                            </Link>
                                        </DropdownMenuItem>
                                    )}
                                    {user.can.delete && (
                                        <DropdownMenuItem
                                            variant="destructive"
                                            onSelect={() => onDelete(user)}
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
