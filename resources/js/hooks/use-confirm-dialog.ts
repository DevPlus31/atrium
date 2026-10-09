import { useState } from 'react';
import type { ConfirmDialogProps } from '@/components/confirm-dialog';

/** The visit options a confirmed action must pass on to its request. */
export type ConfirmedVisitOptions = {
    preserveScroll: true;
    onFinish: () => void;
};

export type UseConfirmDialogReturn<Row> = {
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
 * Confirm-then-act for one row: `run` sends the request (passing `options`
 * on), the dialog stays open with a spinner while it runs and closes when it
 * finishes. Deleting is `useDeleteDialog`.
 */
export function useConfirmDialog<Row>(
    run: (row: Row, options: ConfirmedVisitOptions) => void,
): UseConfirmDialogReturn<Row> {
    const [pending, setPending] = useState<Row | null>(null);
    const [processing, setProcessing] = useState(false);

    const confirm = () => {
        if (pending === null || processing) {
            return;
        }

        setProcessing(true);
        run(pending, {
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
