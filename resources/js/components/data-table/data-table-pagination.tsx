import { useLaravelReactI18n } from 'laravel-react-i18n';
import {
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { UseTableStateReturn } from '@/hooks/use-table-state';
import { PER_PAGE_OPTIONS } from '@/hooks/use-table-state';
import type { PaginationMeta } from '@/types/admin';

export type DataTablePaginationProps = {
    meta: PaginationMeta;
    tableState: UseTableStateReturn;
};

export function DataTablePagination({
    meta,
    tableState,
}: DataTablePaginationProps) {
    const { t } = useLaravelReactI18n();
    const perPageOptions = PER_PAGE_OPTIONS.includes(meta.per_page)
        ? PER_PAGE_OPTIONS
        : [...PER_PAGE_OPTIONS, meta.per_page].sort((a, b) => a - b);

    const rangeLabel =
        meta.total > 0 && meta.from !== null && meta.to !== null
            ? t('Showing :from to :to of :total', {
                  from: meta.from,
                  to: meta.to,
                  total: meta.total,
              })
            : t('No results');

    return (
        <div className="flex flex-wrap items-center justify-between gap-4">
            <p className="text-sm text-muted-foreground">{rangeLabel}</p>
            <div className="flex flex-wrap items-center gap-4 lg:gap-6">
                <div className="flex items-center gap-2">
                    <p className="text-sm font-medium">{t('Rows per page')}</p>
                    <Select
                        value={String(meta.per_page)}
                        onValueChange={(value) =>
                            tableState.setPerPage(Number(value))
                        }
                    >
                        <SelectTrigger className="h-8 w-[4.75rem]">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent side="top">
                            {perPageOptions.map((option) => (
                                <SelectItem key={option} value={String(option)}>
                                    {option}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
                <p className="text-sm font-medium">
                    {t('Page :page of :total', {
                        page: meta.current_page,
                        total: meta.last_page,
                    })}
                </p>
                <div className="flex items-center gap-2">
                    <Button
                        variant="outline"
                        className="hidden size-8 p-0 lg:flex"
                        disabled={meta.current_page <= 1}
                        onClick={() => tableState.setPage(1)}
                    >
                        <span className="sr-only">{t('Go to first page')}</span>
                        <ChevronsLeft className="size-4 rtl:rotate-180" />
                    </Button>
                    <Button
                        variant="outline"
                        className="size-8 p-0"
                        disabled={meta.current_page <= 1}
                        onClick={() =>
                            tableState.setPage(meta.current_page - 1)
                        }
                    >
                        <span className="sr-only">
                            {t('Go to previous page')}
                        </span>
                        <ChevronLeft className="size-4 rtl:rotate-180" />
                    </Button>
                    <Button
                        variant="outline"
                        className="size-8 p-0"
                        disabled={meta.current_page >= meta.last_page}
                        onClick={() =>
                            tableState.setPage(meta.current_page + 1)
                        }
                    >
                        <span className="sr-only">{t('Go to next page')}</span>
                        <ChevronRight className="size-4 rtl:rotate-180" />
                    </Button>
                    <Button
                        variant="outline"
                        className="hidden size-8 p-0 lg:flex"
                        disabled={meta.current_page >= meta.last_page}
                        onClick={() => tableState.setPage(meta.last_page)}
                    >
                        <span className="sr-only">{t('Go to last page')}</span>
                        <ChevronsRight className="size-4 rtl:rotate-180" />
                    </Button>
                </div>
            </div>
        </div>
    );
}
