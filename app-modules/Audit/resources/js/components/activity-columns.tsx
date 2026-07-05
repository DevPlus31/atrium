import type { ColumnDef } from '@tanstack/react-table';
import { DataTableColumnHeader } from '@/components/data-table';
import { Badge } from '@/components/ui/badge';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';

export type ActivityRow = Modules.Audit.Data.ActivityData;

function formatDateTime(value: string): string {
    return new Date(value).toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function EmptyValue() {
    return <span className="text-muted-foreground">—</span>;
}

export function buildActivityColumns(): ColumnDef<ActivityRow, unknown>[] {
    return [
        {
            id: 'created_at',
            accessorKey: 'created_at',
            enableSorting: true,
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title="Date" />
            ),
            cell: ({ row }) => (
                <span className="whitespace-nowrap text-muted-foreground">
                    {formatDateTime(row.original.created_at)}
                </span>
            ),
        },
        {
            id: 'log_name',
            enableSorting: false,
            header: 'Log',
            cell: ({ row }) =>
                row.original.log_name === null ? (
                    <EmptyValue />
                ) : (
                    <Badge variant="secondary">{row.original.log_name}</Badge>
                ),
        },
        {
            id: 'event',
            enableSorting: false,
            header: 'Event',
            cell: ({ row }) =>
                row.original.event === null ? (
                    <EmptyValue />
                ) : (
                    <Badge variant="outline">{row.original.event}</Badge>
                ),
        },
        {
            id: 'description',
            accessorKey: 'description',
            enableSorting: false,
            header: 'Description',
            cell: ({ row }) => (
                <span className="font-medium">{row.original.description}</span>
            ),
        },
        {
            id: 'causer',
            enableSorting: false,
            header: 'Causer',
            cell: ({ row }) => {
                const causer = row.original.causer;

                if (causer === null) {
                    return <EmptyValue />;
                }

                return (
                    <span className="flex flex-col">
                        <span>{causer.name}</span>
                        <span className="text-xs text-muted-foreground">
                            {causer.email}
                        </span>
                    </span>
                );
            },
        },
        {
            id: 'subject',
            enableSorting: false,
            header: 'Subject',
            cell: ({ row }) => {
                const activity = row.original;

                if (activity.subject_type === null) {
                    return <EmptyValue />;
                }

                return (
                    <span className="text-muted-foreground">
                        {activity.subject_type}
                        {activity.subject_id === null
                            ? ''
                            : ` #${activity.subject_id}`}
                    </span>
                );
            },
        },
        {
            id: 'changes',
            enableSorting: false,
            header: 'Changes',
            cell: ({ row }) => {
                const changes = row.original.changes;
                const count = Object.keys(changes).length;

                if (count === 0) {
                    return <EmptyValue />;
                }

                return (
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <span className="cursor-default text-muted-foreground underline decoration-dotted underline-offset-4">
                                {count} {count === 1 ? 'field' : 'fields'}
                            </span>
                        </TooltipTrigger>
                        <TooltipContent>
                            <pre className="max-h-64 overflow-auto text-xs">
                                {JSON.stringify(changes, null, 2)}
                            </pre>
                        </TooltipContent>
                    </Tooltip>
                );
            },
        },
    ];
}
