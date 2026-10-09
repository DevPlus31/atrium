import type { RowData, SortingState, Updater } from '@tanstack/react-table';
import { flexRender, useTable } from '@tanstack/react-table';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { DataTablePagination } from '@/components/data-table/data-table-pagination';
import { dataTableFeatures } from '@/components/data-table/features';
import type {
    DataTableColumn,
    DataTableColumnApi,
} from '@/components/data-table/features';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { RowSelection } from '@/hooks/use-row-selection';
import type { UseTableStateReturn } from '@/hooks/use-table-state';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/admin';

export type DataTableProps<TData extends RowData> = {
    columns: DataTableColumn<TData>[];
    paginated: Paginated<TData>;
    tableState: UseTableStateReturn;
    emptyMessage?: string;
    className?: string;
    /** Adds a checkbox column for bulk actions (see useRowSelection). */
    selection?: RowSelection<TData>;
};

/**
 * Server-driven table shell. TanStack Table runs in fully manual mode —
 * sorting, filtering and pagination live in the URL (via useTableState) and
 * the server is the source of truth. Row/cell vertical padding consumes the
 * `--density` CSS variable so a theme can re-skin density without edits.
 */
/** The ARIA sort state of a sortable column header; undefined otherwise. */
function ariaSort<TData extends RowData>(
    column: DataTableColumnApi<TData>,
): 'ascending' | 'descending' | 'none' | undefined {
    if (!column.getCanSort()) {
        return undefined;
    }

    const sorted = column.getIsSorted();

    if (sorted === 'asc') {
        return 'ascending';
    }

    return sorted === 'desc' ? 'descending' : 'none';
}

export function DataTable<TData extends RowData>({
    columns,
    paginated,
    tableState,
    emptyMessage,
    className,
    selection,
}: DataTableProps<TData>) {
    const { t } = useLaravelReactI18n();
    const sorting: SortingState = tableState.sort
        ? [
              {
                  id: tableState.sort.field,
                  desc: tableState.sort.direction === 'desc',
              },
          ]
        : [];

    const handleSortingChange = (updater: Updater<SortingState>) => {
        const next = typeof updater === 'function' ? updater(sorting) : updater;
        const first = next.at(0);

        tableState.setSort(
            first
                ? { field: first.id, direction: first.desc ? 'desc' : 'asc' }
                : null,
        );
    };

    const table = useTable({
        features: dataTableFeatures,
        data: paginated.data,
        columns,
        state: { sorting },
        manualSorting: true,
        enableSortingRemoval: true,
        onSortingChange: handleSortingChange,
    });

    const rows = table.getRowModel().rows;

    return (
        <div className={cn('flex flex-col gap-4', className)}>
            <div className="rounded-md border">
                <Table>
                    <TableHeader>
                        {table.getHeaderGroups().map((headerGroup) => (
                            <TableRow key={headerGroup.id}>
                                {selection && (
                                    <TableHead className="w-10">
                                        <Checkbox
                                            aria-label={t('Select all rows')}
                                            disabled={!selection.hasSelectable}
                                            checked={
                                                selection.allSelected ||
                                                (selection.someSelected &&
                                                    'indeterminate')
                                            }
                                            onCheckedChange={(checked) =>
                                                selection.toggleAll(
                                                    checked === true,
                                                )
                                            }
                                        />
                                    </TableHead>
                                )}
                                {headerGroup.headers.map((header) => (
                                    <TableHead
                                        key={header.id}
                                        aria-sort={ariaSort(header.column)}
                                    >
                                        {header.isPlaceholder
                                            ? null
                                            : flexRender(
                                                  header.column.columnDef
                                                      .header,
                                                  header.getContext(),
                                              )}
                                    </TableHead>
                                ))}
                            </TableRow>
                        ))}
                    </TableHeader>
                    <TableBody>
                        {rows.length > 0 ? (
                            rows.map((row) => (
                                <TableRow
                                    key={row.id}
                                    data-state={
                                        selection?.isSelected(row.original)
                                            ? 'selected'
                                            : undefined
                                    }
                                >
                                    {selection && (
                                        <TableCell className="w-10 py-[var(--density,0.5rem)]">
                                            <Checkbox
                                                aria-label={t('Select row')}
                                                disabled={
                                                    !selection.canSelect(
                                                        row.original,
                                                    )
                                                }
                                                checked={selection.isSelected(
                                                    row.original,
                                                )}
                                                onCheckedChange={(checked) =>
                                                    selection.toggle(
                                                        row.original,
                                                        checked === true,
                                                    )
                                                }
                                                data-test="select-row"
                                            />
                                        </TableCell>
                                    )}
                                    {row.getAllCells().map((cell) => (
                                        <TableCell
                                            key={cell.id}
                                            className="py-[var(--density,0.5rem)]"
                                        >
                                            {flexRender(
                                                cell.column.columnDef.cell,
                                                cell.getContext(),
                                            )}
                                        </TableCell>
                                    ))}
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell
                                    colSpan={
                                        columns.length + (selection ? 1 : 0)
                                    }
                                    className="h-24 py-[var(--density,0.5rem)] text-center text-muted-foreground"
                                >
                                    {emptyMessage ?? t('No results.')}
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>
            <DataTablePagination
                meta={paginated.meta}
                tableState={tableState}
            />
        </div>
    );
}
