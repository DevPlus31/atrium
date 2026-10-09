import type { RowData } from '@tanstack/react-table';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Ellipsis } from 'lucide-react';
import type { ReactNode } from 'react';
import type { DataTableRow } from '@/components/data-table/features';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

export type DataTableRowActionsProps<TData extends RowData> = {
    row: DataTableRow<TData>;
    /** Renders the dropdown items (DropdownMenuItem etc.) for this row. */
    children: (row: DataTableRow<TData>) => ReactNode;
    label?: string;
};

export function DataTableRowActions<TData extends RowData>({
    row,
    children,
    label,
}: DataTableRowActionsProps<TData>) {
    const { t } = useLaravelReactI18n();

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="size-8 data-[state=open]:bg-muted"
                    data-test="row-actions"
                >
                    <Ellipsis className="size-4" />
                    <span className="sr-only">{label ?? t('Open menu')}</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-40">
                {children(row)}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
