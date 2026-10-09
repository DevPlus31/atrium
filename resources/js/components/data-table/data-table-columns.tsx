import { Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import type { RowData } from '@tanstack/react-table';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { ReactNode } from 'react';
import { DataTableColumnHeader } from '@/components/data-table/data-table-column-header';
import { DataTableRowActions } from '@/components/data-table/data-table-row-actions';
import type { DataTableColumn } from '@/components/data-table/features';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';

/** The placeholder for an empty cell. */
export function EmptyValue() {
    return <span className="text-muted-foreground">—</span>;
}

/**
 * A column the server can sort by its field `id` (the URL's `sort` value),
 * with a sort-toggling header; `cell` renders the row (the plain value when
 * omitted).
 */
export function sortableColumn<TData extends RowData>({
    id,
    title,
    cell,
}: {
    id: Extract<keyof TData, string>;
    title: string;
    cell?: (row: TData) => ReactNode;
}): DataTableColumn<TData> {
    return {
        id,
        accessorFn: (row) => row[id],
        enableSorting: true,
        header: ({ column }) => (
            <DataTableColumnHeader column={column} title={title} />
        ),
        cell: ({ row }) =>
            cell ? cell(row.original) : String(row.original[id] ?? ''),
    };
}

/**
 * A sortable date column: the server sorts by `id`, the cell shows
 * `format(value(row))` (e.g. `format.date` or `format.dateTime`).
 */
export function dateColumn<TData extends RowData>({
    id,
    title,
    value,
    format,
}: {
    id: string;
    title: string;
    value: (row: TData) => string;
    format: (value: string) => string;
}): DataTableColumn<TData> {
    return {
        id,
        accessorFn: value,
        enableSorting: true,
        header: ({ column }) => (
            <DataTableColumnHeader column={column} title={title} />
        ),
        cell: ({ row }) => (
            <span className="whitespace-nowrap text-muted-foreground">
                {format(value(row.original))}
            </span>
        ),
    };
}

/**
 * The trailing row-menu column. `visible` says whether the row has any
 * action (no menu at all otherwise); `items` renders the menu entries.
 */
export function actionsColumn<TData extends RowData>({
    label,
    visible,
    items,
}: {
    /** Screen-reader header, e.g. t('Actions'). */
    label: string;
    visible: (row: TData) => boolean;
    items: (row: TData) => ReactNode;
}): DataTableColumn<TData> {
    return {
        id: 'actions',
        enableSorting: false,
        header: () => <span className="sr-only">{label}</span>,
        cell: ({ row }) =>
            visible(row.original) ? (
                <div className="flex justify-end">
                    <DataTableRowActions row={row}>
                        {() => items(row.original)}
                    </DataTableRowActions>
                </div>
            ) : null,
    };
}

/** "Edit" in a row menu, linking to the edit page. */
export function EditMenuItem({
    href,
}: {
    href: NonNullable<InertiaLinkProps['href']>;
}) {
    const { t } = useLaravelReactI18n();

    return (
        <DropdownMenuItem asChild>
            <Link href={href}>{t('Edit')}</Link>
        </DropdownMenuItem>
    );
}

/** "Delete" in a row menu (destructive; usually opens a confirmation). */
export function DeleteMenuItem({ onSelect }: { onSelect: () => void }) {
    const { t } = useLaravelReactI18n();

    return (
        <DropdownMenuItem variant="destructive" onSelect={onSelect}>
            {t('Delete')}
        </DropdownMenuItem>
    );
}
