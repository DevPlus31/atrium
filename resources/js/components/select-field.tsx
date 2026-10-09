import type { ReactNode } from 'react';
import { FormField } from '@/components/form-field';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export type SelectOption = {
    value: string;
    label: ReactNode;
};

export type SelectFieldProps = {
    id: string;
    label: ReactNode;
    value: string;
    onValueChange: (value: string) => void;
    options: SelectOption[];
    error?: string;
    hint?: ReactNode;
    /** Width of the trigger, e.g. `w-full sm:w-60`. */
    triggerClassName?: string;
};

/** A labelled dropdown of fixed options, with its validation message. */
export function SelectField({
    id,
    label,
    value,
    onValueChange,
    options,
    error,
    hint,
    triggerClassName,
}: SelectFieldProps) {
    return (
        <FormField id={id} label={label} error={error} hint={hint}>
            <Select value={value} onValueChange={onValueChange}>
                <SelectTrigger id={id} className={triggerClassName}>
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </FormField>
    );
}
