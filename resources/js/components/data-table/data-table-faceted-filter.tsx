import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Check, CirclePlus } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
    CommandSeparator,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Separator } from '@/components/ui/separator';
import type { UseTableStateReturn } from '@/hooks/use-table-state';
import { cn } from '@/lib/utils';

export type FacetedFilterOption = {
    label: string;
    value: string;
    icon?: LucideIcon;
};

export type DataTableFacetedFilterProps = {
    tableState: UseTableStateReturn;
    /** Server filter field name — written to `filter[<field>]` as CSV. */
    field: string;
    title: string;
    options: FacetedFilterOption[];
};

export function DataTableFacetedFilter({
    tableState,
    field,
    title,
    options,
}: DataTableFacetedFilterProps) {
    const { t } = useLaravelReactI18n();
    const selected = new Set(tableState.filters[field] ?? []);

    const toggleValue = (value: string) => {
        const next = new Set(selected);

        if (next.has(value)) {
            next.delete(value);
        } else {
            next.add(value);
        }

        tableState.setFilter(field, [...next]);
    };

    return (
        <Popover>
            <PopoverTrigger asChild>
                <Button
                    variant="outline"
                    size="sm"
                    className="h-8 border-dashed"
                >
                    <CirclePlus className="size-4" />
                    {title}
                    {selected.size > 0 && (
                        <>
                            <Separator
                                orientation="vertical"
                                className="mx-1 h-4"
                            />
                            <Badge
                                variant="secondary"
                                className="rounded-sm px-1 font-normal lg:hidden"
                            >
                                {selected.size}
                            </Badge>
                            <div className="hidden gap-1 lg:flex">
                                {selected.size > 2 ? (
                                    <Badge
                                        variant="secondary"
                                        className="rounded-sm px-1 font-normal"
                                    >
                                        {t(':count selected', {
                                            count: selected.size,
                                        })}
                                    </Badge>
                                ) : (
                                    options
                                        .filter((option) =>
                                            selected.has(option.value),
                                        )
                                        .map((option) => (
                                            <Badge
                                                key={option.value}
                                                variant="secondary"
                                                className="rounded-sm px-1 font-normal"
                                            >
                                                {option.label}
                                            </Badge>
                                        ))
                                )}
                            </div>
                        </>
                    )}
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-52 p-0" align="start">
                <Command>
                    <CommandInput placeholder={title} />
                    <CommandList>
                        <CommandEmpty>{t('No results found.')}</CommandEmpty>
                        <CommandGroup>
                            {options.map((option) => {
                                const isSelected = selected.has(option.value);

                                return (
                                    <CommandItem
                                        key={option.value}
                                        onSelect={() =>
                                            toggleValue(option.value)
                                        }
                                    >
                                        <div
                                            className={cn(
                                                'flex size-4 items-center justify-center rounded-sm border border-primary',
                                                isSelected
                                                    ? 'bg-primary text-primary-foreground'
                                                    : 'opacity-50 [&_svg]:invisible',
                                            )}
                                        >
                                            <Check className="size-3.5" />
                                        </div>
                                        {option.icon && (
                                            <option.icon className="size-4 text-muted-foreground" />
                                        )}
                                        <span>{option.label}</span>
                                    </CommandItem>
                                );
                            })}
                        </CommandGroup>
                        {selected.size > 0 && (
                            <>
                                <CommandSeparator />
                                <CommandGroup>
                                    <CommandItem
                                        onSelect={() =>
                                            tableState.setFilter(field, [])
                                        }
                                        className="justify-center text-center"
                                    >
                                        {t('Clear filters')}
                                    </CommandItem>
                                </CommandGroup>
                            </>
                        )}
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}
