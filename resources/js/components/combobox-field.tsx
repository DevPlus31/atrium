import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Check, ChevronsUpDown } from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { FormField } from '@/components/form-field';
import type { SelectOption } from '@/components/select-field';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

export type ComboboxOption = SelectOption & {
    /** What the search matches besides the value (e.g. the full name). */
    keywords?: string[];
};

/**
 * A labelled, searchable dropdown for long option lists (time zones,
 * currencies), with its validation message.
 */
export function ComboboxField({
    id,
    label,
    value,
    onValueChange,
    options,
    error,
    hint,
    searchPlaceholder,
}: {
    id: string;
    label: ReactNode;
    value: string;
    onValueChange: (value: string) => void;
    options: ComboboxOption[];
    error?: string;
    hint?: ReactNode;
    searchPlaceholder: string;
}) {
    const { t } = useLaravelReactI18n();
    const [open, setOpen] = useState(false);
    const selected = options.find((option) => option.value === value);

    return (
        <FormField id={id} label={label} error={error} hint={hint}>
            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger asChild>
                    <Button
                        id={id}
                        variant="outline"
                        role="combobox"
                        aria-expanded={open}
                        className="w-full justify-between font-normal sm:w-80"
                    >
                        <span className="truncate">
                            {selected?.label ?? value}
                        </span>
                        <ChevronsUpDown
                            className="size-4 opacity-50"
                            aria-hidden="true"
                        />
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-(--radix-popover-trigger-width) p-0 sm:w-80">
                    <Command>
                        <CommandInput placeholder={searchPlaceholder} />
                        <CommandList>
                            <CommandEmpty>{t('No results.')}</CommandEmpty>
                            <CommandGroup>
                                {options.map((option) => (
                                    <CommandItem
                                        key={option.value}
                                        value={option.value}
                                        keywords={option.keywords}
                                        onSelect={() => {
                                            setOpen(false);
                                            onValueChange(option.value);
                                        }}
                                    >
                                        <Check
                                            className={cn(
                                                'size-4',
                                                option.value === value
                                                    ? 'opacity-100'
                                                    : 'opacity-0',
                                            )}
                                            aria-hidden="true"
                                        />
                                        {option.label}
                                    </CommandItem>
                                ))}
                            </CommandGroup>
                        </CommandList>
                    </Command>
                </PopoverContent>
            </Popover>
        </FormField>
    );
}
