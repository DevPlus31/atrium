import { router } from '@inertiajs/react';
import { useConfirmDialog } from '@/hooks/use-confirm-dialog';
import type { UseConfirmDialogReturn } from '@/hooks/use-confirm-dialog';
import type { RowSelection } from '@/hooks/use-row-selection';

export type UseBulkDeleteReturn = {
    /** Open the confirmation for the selected rows. */
    request: () => void;
    /** Spread onto ConfirmDialog. */
    dialogProps: UseConfirmDialogReturn<string[]>['dialogProps'];
};

/**
 * Confirm-then-delete for a table's selected rows: DELETE `url` with the
 * selected `ids`, then clear the selection.
 */
export function useBulkDelete<TData>(
    url: string,
    selection: RowSelection<TData>,
): UseBulkDeleteReturn {
    const dialog = useConfirmDialog<string[]>((ids, options) =>
        router.delete(url, {
            data: { ids },
            onSuccess: () => selection.clear(),
            ...options,
        }),
    );

    return {
        request: () => dialog.request(selection.selectedIds),
        dialogProps: dialog.dialogProps,
    };
}
