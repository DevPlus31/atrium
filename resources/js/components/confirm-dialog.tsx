import { useLaravelReactI18n } from 'laravel-react-i18n';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Spinner } from '@/components/ui/spinner';

export type ConfirmDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description: string;
    confirmLabel?: string;
    cancelLabel?: string;
    /** Renders the confirm button with the destructive variant. */
    destructive?: boolean;
    /** Disables both buttons and shows a spinner on the confirm button. */
    processing?: boolean;
    onConfirm: () => void;
};

/**
 * Controlled confirmation dialog for destructive (and other guarded)
 * actions. Confirming does not close it: the caller owns the `open` state
 * and closes the dialog when the action finishes (see useDeleteDialog).
 */
export function ConfirmDialog({
    open,
    onOpenChange,
    title,
    description,
    confirmLabel,
    cancelLabel,
    destructive = true,
    processing = false,
    onConfirm,
}: ConfirmDialogProps) {
    const { t } = useLaravelReactI18n();

    return (
        <AlertDialog open={open} onOpenChange={onOpenChange}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{title}</AlertDialogTitle>
                    <AlertDialogDescription>
                        {description}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel disabled={processing}>
                        {cancelLabel ?? t('Cancel')}
                    </AlertDialogCancel>
                    <AlertDialogAction
                        variant={destructive ? 'destructive' : 'default'}
                        disabled={processing}
                        onClick={(event) => {
                            // Keep the dialog open; the caller closes it
                            // once the confirmed action has finished.
                            event.preventDefault();
                            onConfirm();
                        }}
                    >
                        {processing && <Spinner />}
                        {confirmLabel ?? t('Confirm')}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
