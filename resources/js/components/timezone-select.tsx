import { usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Check, ChevronsUpDown } from 'lucide-react';
import { useState } from 'react';
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
import { persistPreferences } from '@/lib/preferences';
import { cn } from '@/lib/utils';

/**
 * The user's timezone preference: "Automatic" follows the browser, any
 * IANA zone pins dates to it everywhere (server-rendered pages, emails,
 * notifications). Zones come from the runtime (Intl) and the server
 * validates them.
 */
export function TimezoneSelect({ id }: { id?: string }) {
    const { t } = useLaravelReactI18n();
    const { timezone } = usePage().props;
    const [open, setOpen] = useState(false);

    const choose = (value: string | null) => {
        setOpen(false);
        persistPreferences({ timezone: value });
    };

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    id={id}
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    className="w-full justify-between font-normal sm:w-80"
                >
                    {timezone ?? t('Automatic (this browser)')}
                    <ChevronsUpDown
                        className="size-4 opacity-50"
                        aria-hidden="true"
                    />
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-(--radix-popover-trigger-width) p-0 sm:w-80">
                <Command>
                    <CommandInput placeholder={t('Search timezones...')} />
                    <CommandList>
                        <CommandEmpty>{t('No timezone found.')}</CommandEmpty>
                        <CommandGroup>
                            <CommandItem
                                value="automatic"
                                onSelect={() => choose(null)}
                            >
                                <Check
                                    className={cn(
                                        'size-4',
                                        timezone === null
                                            ? 'opacity-100'
                                            : 'opacity-0',
                                    )}
                                    aria-hidden="true"
                                />
                                {t('Automatic (this browser)')}
                            </CommandItem>
                            {Intl.supportedValuesOf('timeZone').map((zone) => (
                                <CommandItem
                                    key={zone}
                                    value={zone}
                                    onSelect={() => choose(zone)}
                                >
                                    <Check
                                        className={cn(
                                            'size-4',
                                            timezone === zone
                                                ? 'opacity-100'
                                                : 'opacity-0',
                                        )}
                                        aria-hidden="true"
                                    />
                                    {zone}
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}
