import type { Column } from '@tanstack/react-table';
import type { LucideIcon } from 'lucide-react';
import { ArrowDown, ArrowUp, ChevronsUpDown } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

export type DataTableColumnHeaderProps<TData, TValue> = {
    column: Column<TData, TValue>;
    title: string;
    className?: string;
};

/**
 * Click-to-sort column header for sortable columns. Cycles
 * asc → desc → none; the sort change flows through the table's manual
 * sorting handler into the URL state.
 */
export function DataTableColumnHeader<TData, TValue>({
    column,
    title,
    className,
}: DataTableColumnHeaderProps<TData, TValue>) {
    if (!column.getCanSort()) {
        return <span className={cn(className)}>{title}</span>;
    }

    const sorted = column.getIsSorted();

    let SortIcon: LucideIcon = ChevronsUpDown;

    if (sorted === 'asc') {
        SortIcon = ArrowUp;
    }

    if (sorted === 'desc') {
        SortIcon = ArrowDown;
    }

    const cycleSort = () => {
        if (sorted === false) {
            column.toggleSorting(false);
            return;
        }

        if (sorted === 'asc') {
            column.toggleSorting(true);
            return;
        }

        column.clearSorting();
    };

    return (
        <Button
            variant="ghost"
            size="sm"
            className={cn('-ms-3 h-8 data-[state=open]:bg-accent', className)}
            onClick={cycleSort}
        >
            <span>{title}</span>
            <SortIcon
                className={cn(
                    'size-4',
                    sorted === false && 'text-muted-foreground/70',
                )}
            />
        </Button>
    );
}
