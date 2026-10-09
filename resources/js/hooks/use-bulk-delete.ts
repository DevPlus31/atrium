import { router } from '@inertiajs/react';
import { useState } from 'react';
import type { ConfirmDialogProps } from '@/components/confirm-dialog';
import type { RowSelection } from '@/hooks/use-row-selection';

export type UseBulkDeleteReturn = {
    /** Open the confirmation for the selected rows. */
    request: () => void;
    /** Spread onto ConfirmDialog. */
    dialogProps: Pick<
        ConfirmDialogProps,
        'open' | 'onOpenChange' | 'processing' | 'onConfirm'
    >;
};

/**
 * Confirm-then-delete for a table's selected rows: DELETE `url` with the
 * selected `ids`, then clear the selection.
 */
export function useBulkDelete<TData>(
    url: string,
    selection: RowSelection<TData>,
): UseBulkDeleteReturn {
    const [open, setOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    const confirm = () => {
        if (processing) {
            return;
        }

        setProcessing(true);
        router.delete(url, {
            data: { ids: selection.selectedIds },
            preserveScroll: true,
            onSuccess: () => selection.clear(),
            onFinish: () => {
                setProcessing(false);
                setOpen(false);
            },
        });
    };

    return {
        request: () => setOpen(true),
        dialogProps: {
            open,
            onOpenChange: (next: boolean) => {
                if (!processing) {
                    setOpen(next);
                }
            },
            processing,
            onConfirm: confirm,
        },
    };
}
