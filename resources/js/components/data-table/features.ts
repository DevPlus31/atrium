import { rowSortingFeature, tableFeatures } from '@tanstack/react-table';
import type { Column, ColumnDef, Row, RowData } from '@tanstack/react-table';

/**
 * The feature set every admin data table registers. Tables are server
 * driven: sorting runs in manual mode (the URL owns it), while filtering and
 * pagination never touch TanStack at all, so only row sorting is needed.
 */
export const dataTableFeatures = tableFeatures({ rowSortingFeature });

export type DataTableFeatures = typeof dataTableFeatures;

/** A column definition for an admin data table. */
export type DataTableColumn<TData extends RowData> = ColumnDef<
    DataTableFeatures,
    TData,
    unknown
>;

export type DataTableColumnApi<
    TData extends RowData,
    TValue = unknown,
> = Column<DataTableFeatures, TData, TValue>;

export type DataTableRow<TData extends RowData> = Row<DataTableFeatures, TData>;
