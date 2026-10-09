import { useBulkDelete } from '@/hooks/use-bulk-delete';
import type { UseBulkDeleteReturn } from '@/hooks/use-bulk-delete';
import type { UseConfirmDialogReturn } from '@/hooks/use-confirm-dialog';
import { useDeleteDialog } from '@/hooks/use-delete-dialog';
import { useRowSelection } from '@/hooks/use-row-selection';
import type { RowSelection } from '@/hooks/use-row-selection';
import { useTableState } from '@/hooks/use-table-state';
import type { UseTableStateReturn } from '@/hooks/use-table-state';

/** What a resource row brings for the table: its key and delete ability. */
export type ResourceRow = {
    id: string | number;
    can: { delete: boolean };
};

export type UseResourceTableOptions<TRow extends ResourceRow> = {
    /** The page prop holding the paginated rows (partial reloads ask for it). */
    key: string;
    /** The rows of the current page. */
    rows: TRow[];
    /** The DELETE URL of one row. */
    destroyUrl: (row: TRow) => string;
    /** The bulk DELETE URL; without it no row can be selected. */
    bulkDestroyUrl?: string;
};

export type UseResourceTableReturn<TRow extends ResourceRow> = {
    tableState: UseTableStateReturn;
    deleteDialog: UseConfirmDialogReturn<TRow>;
    selection: RowSelection<TRow>;
    bulkDelete: UseBulkDeleteReturn;
};

/**
 * Everything a resource index page wires up: URL-backed table state, the
 * per-row delete confirmation and, with `bulkDestroyUrl`, row selection and
 * the bulk delete confirmation (rows the user may not delete stay unticked).
 */
export function useResourceTable<TRow extends ResourceRow>({
    key,
    rows,
    destroyUrl,
    bulkDestroyUrl,
}: UseResourceTableOptions<TRow>): UseResourceTableReturn<TRow> {
    const tableState = useTableState(key);
    const deleteDialog = useDeleteDialog<TRow>(destroyUrl);
    const selection = useRowSelection(
        rows,
        (row) => String(row.id),
        (row) => bulkDestroyUrl !== undefined && row.can.delete,
    );
    const bulkDelete = useBulkDelete(bulkDestroyUrl ?? '', selection);

    return { tableState, deleteDialog, selection, bulkDelete };
}
