import type { InputHTMLAttributes, ReactNode } from 'react';
import { CurrencyInput } from '@/components/currency-input';
import { FormField } from '@/components/form-field';
import PasswordInput from '@/components/password-input';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import type { FormFieldName, FormLike } from '@/types';

type ControlProps = Omit<
    InputHTMLAttributes<HTMLInputElement>,
    'id' | 'name' | 'type' | 'value' | 'onChange' | 'onBlur' | 'form'
>;

export type TextFieldProps<
    TData,
    TName extends FormFieldName<TData>,
> = ControlProps & {
    /** The page's `useForm()` result. */
    form: FormLike<TData>;
    /** The form field: its value, its error and the control's default id. */
    name: TName;
    label: ReactNode;
    /** Defaults to the field name. */
    id?: string;
    /**
     * `number` stores a number; `password` gets the show/hide toggle;
     * `currency` is a three-letter code kept upper case.
     */
    type?:
        | 'text'
        | 'email'
        | 'password'
        | 'number'
        | 'currency'
        | 'url'
        | 'tel'
        | 'datetime-local';
    /** Render a textarea with this many rows instead of an input. */
    rows?: number;
    /** The field to validate on blur, when not this one (a confirmation). */
    validates?: FormFieldName<TData>;
    /**
     * Validate on blur through Precognition (default). Turn it off when the
     * route has no `HandlePrecognitiveRequests` middleware: a validate call
     * there would run the real request.
     */
    validateOnBlur?: boolean;
    hint?: ReactNode;
    labelAside?: ReactNode;
    hideLabel?: boolean;
    fieldClassName?: string;
};

/**
 * A labelled text input bound to an Inertia form field: value, change,
 * validate-on-blur (Precognition) and the error, from `form` and `name`.
 */
export function TextField<TData, TName extends FormFieldName<TData>>({
    form,
    name,
    label,
    id = name,
    type = 'text',
    rows,
    validates,
    validateOnBlur = true,
    hint,
    labelAside,
    hideLabel,
    fieldClassName,
    ...control
}: TextFieldProps<TData, TName>) {
    const value = form.data[name];
    const shared = {
        id,
        name,
        value: value === null || value === undefined ? '' : String(value),
        onBlur: validateOnBlur
            ? () => form.validate?.(validates ?? name)
            : undefined,
    };
    const update = (raw: string) =>
        form.setData(
            name,
            (type === 'number' ? Number(raw) : raw) as TData[TName],
        );

    return (
        <FormField
            id={id}
            label={label}
            error={form.errors[name]}
            hint={hint}
            labelAside={labelAside}
            hideLabel={hideLabel}
            className={fieldClassName}
        >
            {rows !== undefined ? (
                <Textarea
                    {...shared}
                    rows={rows}
                    placeholder={control.placeholder}
                    autoFocus={control.autoFocus}
                    maxLength={control.maxLength}
                    onChange={(event) => update(event.target.value)}
                />
            ) : type === 'currency' ? (
                <CurrencyInput
                    {...control}
                    {...shared}
                    onChange={(currency) => update(currency)}
                />
            ) : type === 'password' ? (
                <PasswordInput
                    {...control}
                    {...shared}
                    onChange={(event) => update(event.target.value)}
                />
            ) : (
                <Input
                    {...control}
                    {...shared}
                    type={type}
                    onChange={(event) => update(event.target.value)}
                />
            )}
        </FormField>
    );
}
