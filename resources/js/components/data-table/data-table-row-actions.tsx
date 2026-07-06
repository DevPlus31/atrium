import type { Row } from '@tanstack/react-table';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Ellipsis } from 'lucide-react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

export type DataTableRowActionsProps<TData> = {
    row: Row<TData>;
    /** Renders the dropdown items (DropdownMenuItem etc.) for this row. */
    children: (row: Row<TData>) => ReactNode;
    label?: string;
};

export function DataTableRowActions<TData>({
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
