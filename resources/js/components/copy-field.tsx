import { Check, Copy } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useClipboard } from '@/hooks/use-clipboard';
import { cn } from '@/lib/utils';

export type CopyButtonProps = {
    value: string;
    /** Accessible name, e.g. "Copy token". */
    label: string;
};

/** An icon button that copies `value`, showing a check once it is copied. */
export function CopyButton({ value, label }: CopyButtonProps) {
    const [copied, copy] = useClipboard();

    return (
        <Button
            type="button"
            variant="outline"
            size="icon"
            aria-label={label}
            onClick={() => void copy(value)}
        >
            {copied === value ? (
                <Check aria-hidden="true" />
            ) : (
                <Copy aria-hidden="true" />
            )}
        </Button>
    );
}

export type CopyFieldProps = {
    value: string;
    /** Accessible name of the read-only field, e.g. "New API token". */
    label: string;
    /** Accessible name of the copy button. */
    copyLabel: string;
    inputClassName?: string;
};

/** A read-only value (selected on focus) next to its copy button. */
export function CopyField({
    value,
    label,
    copyLabel,
    inputClassName,
}: CopyFieldProps) {
    return (
        <div className="flex w-full gap-2">
            <Input
                value={value}
                readOnly
                aria-label={label}
                className={cn('font-mono', inputClassName)}
                onFocus={(event) => event.target.select()}
            />
            <CopyButton value={value} label={copyLabel} />
        </div>
    );
}
