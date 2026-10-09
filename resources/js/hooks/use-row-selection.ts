import { useState } from 'react';

export type RowSelection<TData> = {
    /** Selected rows of the current page only; other pages are never kept. */
    selectedIds: string[];
    /** Whether any row on the page may be selected at all. */
    hasSelectable: boolean;
    allSelected: boolean;
    someSelected: boolean;
    canSelect: (row: TData) => boolean;
    isSelected: (row: TData) => boolean;
    toggle: (row: TData, checked: boolean) => void;
    toggleAll: (checked: boolean) => void;
    clear: () => void;
};

/**
 * Row selection for a server-paginated data table. A row the user may not
 * act on (canSelect) cannot be ticked; the server checks every row again.
 */
export function useRowSelection<TData>(
    rows: TData[],
    getId: (row: TData) => string,
    canSelect: (row: TData) => boolean = () => true,
): RowSelection<TData> {
    const [selected, setSelected] = useState<ReadonlySet<string>>(new Set());

    const selectable = rows.filter(canSelect);
    const selectedIds = selectable.map(getId).filter((id) => selected.has(id));

    return {
        selectedIds,
        hasSelectable: selectable.length > 0,
        allSelected:
            selectable.length > 0 && selectedIds.length === selectable.length,
        someSelected:
            selectedIds.length > 0 && selectedIds.length < selectable.length,
        canSelect,
        isSelected: (row) => selected.has(getId(row)),
        toggle: (row, checked) =>
            setSelected((current) => {
                const next = new Set(current);

                if (checked) {
                    next.add(getId(row));
                } else {
                    next.delete(getId(row));
                }

                return next;
            }),
        toggleAll: (checked) =>
            setSelected(new Set(checked ? selectable.map(getId) : [])),
        clear: () => setSelected(new Set()),
    };
}
