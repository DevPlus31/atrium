import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

/** A single checkbox with its label, optional help text and error. */
export function CheckboxField({
    id,
    name,
    label,
    hint,
    error,
    checked,
    onCheckedChange,
}: {
    id: string;
    /** For forms submitted by the browser (Inertia's `<Form>`). */
    name?: string;
    label: ReactNode;
    hint?: ReactNode;
    error?: string;
    checked: boolean;
    onCheckedChange: (checked: boolean) => void;
}) {
    return (
        <div
            className={cn(
                'flex gap-3',
                hint === undefined ? 'items-center' : 'items-start',
            )}
        >
            <Checkbox
                id={id}
                name={name}
                checked={checked}
                onCheckedChange={(value) => onCheckedChange(value === true)}
            />
            <div className="grid gap-1">
                <Label htmlFor={id}>{label}</Label>
                {hint !== undefined && (
                    <p className="text-sm text-muted-foreground">{hint}</p>
                )}
                <InputError message={error} />
            </div>
        </div>
    );
}
