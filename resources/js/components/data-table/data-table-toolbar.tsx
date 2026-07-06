import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Search, X } from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { UseTableStateReturn } from '@/hooks/use-table-state';
import { cn } from '@/lib/utils';

export type DataTableToolbarProps = {
    tableState: UseTableStateReturn;
    searchPlaceholder?: string;
    /** Faceted filter slot (e.g. DataTableFacetedFilter instances). */
    children?: ReactNode;
    /** Right-side actions slot (e.g. Export / Create buttons). */
    actions?: ReactNode;
    className?: string;
};

export function DataTableToolbar({
    tableState,
    searchPlaceholder,
    children,
    actions,
    className,
}: DataTableToolbarProps) {
    const { t } = useLaravelReactI18n();
    const [searchValue, setSearchValue] = useState(tableState.search);
    const showReset = tableState.hasActiveFilters || searchValue !== '';

    return (
        <div className={cn('flex flex-wrap items-center gap-2', className)}>
            <div className="relative">
                <Search className="absolute start-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    type="search"
                    value={searchValue}
                    onChange={(event) => {
                        setSearchValue(event.target.value);
                        tableState.setSearch(event.target.value);
                    }}
                    placeholder={searchPlaceholder ?? t('Search...')}
                    className="h-8 w-40 ps-8 lg:w-64"
                />
            </div>
            {children}
            {showReset && (
                <Button
                    variant="ghost"
                    size="sm"
                    className="h-8 px-2"
                    onClick={() => {
                        setSearchValue('');
                        tableState.reset();
                    }}
                >
                    {t('Reset')}
                    <X className="size-4" />
                </Button>
            )}
            {actions && (
                <div className="ms-auto flex items-center gap-2">{actions}</div>
            )}
        </div>
    );
}
