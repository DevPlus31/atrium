import type { ReactNode } from 'react';
import { EmptyState } from '@/components/empty-state';
import InputError from '@/components/input-error';
import { Checkbox } from '@/components/ui/checkbox';
import { toggleValue } from '@/lib/selection';
import { cn } from '@/lib/utils';

export type CheckboxGroupFieldProps = {
    legend: string;
    /** At the end of the legend line (e.g. "Select all"). */
    legendAction?: ReactNode;
    /** Shown instead of the options when there are none. */
    emptyMessage?: string;
    isEmpty?: boolean;
    error?: string;
    children: ReactNode;
};

/**
 * A group of checkboxes: a fieldset whose legend names the group for screen
 * readers, the options, and the group's validation message.
 */
export function CheckboxGroupField({
    legend,
    legendAction,
    emptyMessage,
    isEmpty = false,
    error,
    children,
}: CheckboxGroupFieldProps) {
    return (
        <fieldset className="grid gap-2">
            <div className="mb-2 flex items-center justify-between gap-2">
                <legend className="text-sm leading-none font-medium">
                    {legend}
                </legend>
                {legendAction}
            </div>
            {isEmpty ? <EmptyState>{emptyMessage}</EmptyState> : children}
            <InputError message={error} />
        </fieldset>
    );
}

export type CheckboxListProps = {
    options: string[];
    selected: string[];
    onChange: (selected: string[]) => void;
    className?: string;
    optionClassName?: string;
};

/** One checkbox per option, each labelled with the option itself. */
export function CheckboxList({
    options,
    selected,
    onChange,
    className,
    optionClassName = 'text-sm',
}: CheckboxListProps) {
    return (
        <div className={cn('grid gap-2', className)}>
            {options.map((option) => (
                <label
                    key={option}
                    className={cn('flex items-center gap-2', optionClassName)}
                >
                    <Checkbox
                        checked={selected.includes(option)}
                        onCheckedChange={(checked) =>
                            onChange(
                                toggleValue(selected, option, checked === true),
                            )
                        }
                    />
                    {option}
                </label>
            ))}
        </div>
    );
}
