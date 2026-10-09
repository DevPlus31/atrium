import { useLaravelReactI18n } from 'laravel-react-i18n';
import {
    dateColumn,
    EmptyValue,
    type DataTableColumn,
} from '@/components/data-table';
import { Badge } from '@/components/ui/badge';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import type { Formatters } from '@/lib/format';
import type { Translator } from '@/types/ui';

export type ActivityRow = Modules.Audit.Data.ActivityData;

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
    format: Formatters,
): DataTableColumn<ActivityRow>[] {
    return [
        dateColumn<ActivityRow>({
            id: 'created_at',
            title: t('Date'),
            value: (activity) => activity.created_at,
            format: format.dateTime,
        }),
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
                <span className="font-medium">
                    {t(row.original.description)}
                </span>
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
