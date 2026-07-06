import type { ColumnDef } from '@tanstack/react-table';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { DataTableColumnHeader } from '@/components/data-table';
import { Badge } from '@/components/ui/badge';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import type { Translator } from '@/types/ui';

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

function ChangesCell({ changes }: { changes: Record<string, unknown> }) {
    const { tChoice } = useLaravelReactI18n();
    const count = Object.keys(changes).length;

    if (count === 0) {
        return <EmptyValue />;
    }

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <span className="cursor-default text-muted-foreground underline decoration-dotted underline-offset-4">
                    {tChoice(':count field|:count fields', count)}
                </span>
            </TooltipTrigger>
            <TooltipContent>
                <pre className="max-h-64 overflow-auto text-xs">
                    {JSON.stringify(changes, null, 2)}
                </pre>
            </TooltipContent>
        </Tooltip>
    );
}

export function buildActivityColumns(
    t: Translator,
): ColumnDef<ActivityRow, unknown>[] {
    return [
        {
            id: 'created_at',
            accessorKey: 'created_at',
            enableSorting: true,
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title={t('Date')} />
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
            header: t('Log'),
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
            header: t('Event'),
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
            header: t('Description'),
            cell: ({ row }) => (
                <span className="font-medium">{row.original.description}</span>
            ),
        },
        {
            id: 'causer',
            enableSorting: false,
            header: t('Causer'),
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
            header: t('Subject'),
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
            header: t('Changes'),
            cell: ({ row }) => <ChangesCell changes={row.original.changes} />,
        },
    ];
}
