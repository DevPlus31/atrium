import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

export type FormFieldProps = {
    /** The control's id: the label points at it. */
    id: string;
    label: ReactNode;
    error?: string;
    /** Help text under the control. */
    hint?: ReactNode;
    /** Next to the label, at the end of the line (e.g. a "Forgot?" link). */
    labelAside?: ReactNode;
    /** Keep the label for screen readers only (the placeholder says it). */
    hideLabel?: boolean;
    className?: string;
    children: ReactNode;
};

/** A labelled control with its help text and validation message. */
export function FormField({
    id,
    label,
    error,
    hint,
    labelAside,
    hideLabel = false,
    className,
    children,
}: FormFieldProps) {
    const labelElement = (
        <Label htmlFor={id} className={hideLabel ? 'sr-only' : undefined}>
            {label}
        </Label>
    );

    return (
        <div className={cn('grid gap-2', className)}>
            {labelAside === undefined ? (
                labelElement
            ) : (
                <div className="flex items-center">
                    {labelElement}
                    <span className="ms-auto">{labelAside}</span>
                </div>
            )}
            {children}
            {hint !== undefined && (
                <p className="text-sm text-muted-foreground">{hint}</p>
            )}
            <InputError message={error} />
        </div>
    );
}
