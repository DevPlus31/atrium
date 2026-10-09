import { router } from '@inertiajs/react';
import { useConfirmDialog } from '@/hooks/use-confirm-dialog';
import type { UseConfirmDialogReturn } from '@/hooks/use-confirm-dialog';

/** Confirm-then-DELETE for one row (see useConfirmDialog). */
export function useDeleteDialog<Row>(
    destroyUrl: (row: Row) => string,
): UseConfirmDialogReturn<Row> {
    return useConfirmDialog<Row>((row, options) =>
        router.delete(destroyUrl(row), options),
    );
}
