import type { ColumnDef, SortingState, Updater } from '@tanstack/react-table';
import {
    flexRender,
    getCoreRowModel,
    useReactTable,
} from '@tanstack/react-table';
import { DataTablePagination } from '@/components/data-table/data-table-pagination';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { UseTableStateReturn } from '@/hooks/use-table-state';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/admin';

export type DataTableProps<TData> = {
    columns: ColumnDef<TData, unknown>[];
    paginated: Paginated<TData>;
    tableState: UseTableStateReturn;
    emptyMessage?: string;
    className?: string;
};

/**
 * Server-driven table shell. TanStack Table runs in fully manual mode —
 * sorting, filtering and pagination live in the URL (via useTableState) and
 * the server is the source of truth. Row/cell vertical padding consumes the
 * `--density` CSS variable so a theme can re-skin density without edits.
 */
export function DataTable<TData>({
    columns,
    paginated,
    tableState,
    emptyMessage = 'No results.',
    className,
}: DataTableProps<TData>) {
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

    const table = useReactTable({
        data: paginated.data,
        columns,
        state: { sorting },
        pageCount: paginated.meta.last_page,
        manualSorting: true,
        manualFiltering: true,
        manualPagination: true,
        enableSortingRemoval: true,
        onSortingChange: handleSortingChange,
        getCoreRowModel: getCoreRowModel(),
    });

    const rows = table.getRowModel().rows;

    return (
        <div className={cn('flex flex-col gap-4', className)}>
            <div className="rounded-md border">
                <Table>
                    <TableHeader>
                        {table.getHeaderGroups().map((headerGroup) => (
                            <TableRow key={headerGroup.id}>
                                {headerGroup.headers.map((header) => (
                                    <TableHead key={header.id}>
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
                                <TableRow key={row.id}>
                                    {row.getVisibleCells().map((cell) => (
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
                                    colSpan={columns.length}
                                    className="h-24 py-[var(--density,0.5rem)] text-center text-muted-foreground"
                                >
                                    {emptyMessage}
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
