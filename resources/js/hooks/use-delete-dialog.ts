import { router } from '@inertiajs/react';
import { useState } from 'react';
import type { ConfirmDialogProps } from '@/components/confirm-dialog';

export type UseDeleteDialogReturn<Row> = {
    /** The row awaiting confirmation, or null when the dialog is closed. */
    pending: Row | null;
    /** Open the confirmation dialog for the given row. */
    request: (row: Row) => void;
    /** Spread onto ConfirmDialog: open state, processing and handlers. */
    dialogProps: Pick<
        ConfirmDialogProps,
        'open' | 'onOpenChange' | 'processing' | 'onConfirm'
    >;
};

/**
 * Confirm-then-delete flow shared by index pages: the dialog stays open with
 * a spinner while the DELETE request runs and closes when it finishes.
 */
export function useDeleteDialog<Row>(
    destroyUrl: (row: Row) => string,
): UseDeleteDialogReturn<Row> {
    const [pending, setPending] = useState<Row | null>(null);
    const [processing, setProcessing] = useState(false);

    const confirm = () => {
        if (pending === null || processing) {
            return;
        }

        setProcessing(true);
        router.delete(destroyUrl(pending), {
            preserveScroll: true,
            onFinish: () => {
                setProcessing(false);
                setPending(null);
            },
        });
    };

    return {
        pending,
        request: (row: Row) => setPending(() => row),
        dialogProps: {
            open: pending !== null,
            onOpenChange: (open: boolean) => {
                if (!open && !processing) {
                    setPending(null);
                }
            },
            processing,
            onConfirm: confirm,
        },
    };
}
