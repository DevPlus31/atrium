import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import type { RowSelection } from '@/hooks/use-row-selection';

export type DataTableBulkActionsProps<TData> = {
    selection: RowSelection<TData>;
    /** The actions for the selected rows (e.g. a delete button). */
    children: ReactNode;
};

/**
 * The bar above a data table while rows are selected: how many, what can be
 * done with them, and a way out.
 */
export function DataTableBulkActions<TData>({
    selection,
    children,
}: DataTableBulkActionsProps<TData>) {
    const { t, tChoice } = useLaravelReactI18n();

    if (selection.selectedIds.length === 0) {
        return null;
    }

    return (
        <div
            className="flex flex-wrap items-center gap-2 rounded-md border bg-muted/50 px-3 py-2"
            data-test="bulk-actions"
        >
            <span className="text-sm font-medium">
                {tChoice(
                    ':count selected|:count selected',
                    selection.selectedIds.length,
                )}
            </span>
            <div className="ms-auto flex items-center gap-2">
                {children}
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={selection.clear}
                >
                    {t('Clear selection')}
                </Button>
            </div>
        </div>
    );
}
