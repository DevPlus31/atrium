import { Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Plus } from 'lucide-react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTableBulkActions } from '@/components/data-table/data-table-bulk-actions';
import { Button } from '@/components/ui/button';
import type { UseBulkDeleteReturn } from '@/hooks/use-bulk-delete';
import type { UseConfirmDialogReturn } from '@/hooks/use-confirm-dialog';
import type { RowSelection } from '@/hooks/use-row-selection';

/** The toolbar's primary "Create …" button. */
export function DataTableCreateButton({
    href,
    label,
}: {
    href: NonNullable<InertiaLinkProps['href']>;
    label: string;
}) {
    return (
        <Button size="sm" asChild>
            <Link href={href}>
                <Plus className="size-4" />
                {label}
            </Link>
        </Button>
    );
}

/**
 * "Delete selected" for the selected rows, with its confirmation. Pass the
 * already-pluralised description for the selection size.
 */
export function DataTableBulkDelete<TData>({
    selection,
    bulkDelete,
    title,
    description,
}: {
    selection: RowSelection<TData>;
    bulkDelete: UseBulkDeleteReturn;
    title: string;
    description: (count: number) => string;
}) {
    const { t } = useLaravelReactI18n();

    return (
        <>
            <DataTableBulkActions selection={selection}>
                <Button
                    variant="destructive"
                    size="sm"
                    onClick={bulkDelete.request}
                    data-test="bulk-delete"
                >
                    {t('Delete selected')}
                </Button>
            </DataTableBulkActions>
            <ConfirmDialog
                {...bulkDelete.dialogProps}
                title={title}
                description={description(selection.selectedIds.length)}
                confirmLabel={t('Delete')}
            />
        </>
    );
}

/** The confirmation for deleting one row, naming the row. */
export function DataTableDeleteDialog<Row>({
    dialog,
    title,
    name,
    fallbackName,
}: {
    dialog: UseConfirmDialogReturn<Row>;
    title: string;
    name: (row: Row) => string;
    /** Used while the dialog closes and the row is already gone. */
    fallbackName: string;
}) {
    const { t } = useLaravelReactI18n();

    return (
        <ConfirmDialog
            {...dialog.dialogProps}
            title={title}
            description={t(
                'This will permanently delete :name and cannot be undone.',
                {
                    name:
                        dialog.pending === null
                            ? fallbackName
                            : name(dialog.pending),
                },
            )}
            confirmLabel={t('Delete')}
        />
    );
}
