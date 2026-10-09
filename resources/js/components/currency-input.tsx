import type { ComponentProps } from 'react';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

type CurrencyInputProps = Omit<
    ComponentProps<typeof Input>,
    'value' | 'onChange' | 'type'
> & {
    value: string;
    onChange: (currency: string) => void;
};

/** A three-letter ISO currency code, kept upper case as it is typed. */
export function CurrencyInput({
    value,
    onChange,
    className,
    ...props
}: CurrencyInputProps) {
    return (
        <Input
            type="text"
            maxLength={3}
            autoComplete="off"
            placeholder="USD"
            {...props}
            className={cn('uppercase', className)}
            value={value}
            onChange={(event) => onChange(event.target.value.toUpperCase())}
        />
    );
}
