import type { ReactNode } from 'react';

export type AppVariant = 'header' | 'sidebar';

export type FlashToast = {
    type: 'success' | 'info' | 'warning' | 'error';
    message: string;
};

/**
 * The translate function shape from laravel-react-i18n's hook, for passing
 * `t` into non-component modules (e.g. column builders) that cannot call
 * hooks themselves.
 */
export type Translator = (
    key: string,
    replacements?: Record<string, string | number>,
) => string;

export type AuthLayoutProps = {
    children?: ReactNode;
    title: string;
    description?: string;
};

/** The keys of a form's data that name a field (string keys only). */
export type FormFieldName<TData> = Extract<keyof TData, string>;

/**
 * The part of an Inertia `useForm()` result a field needs: pass the form
 * itself (`form={form}`). `validate` is there when the route is
 * precognitive; fields validate themselves on blur when it is.
 */
export type FormLike<TData> = {
    data: TData;
    errors: Partial<Record<keyof TData, string>>;
    setData: <K extends keyof TData>(field: K, value: TData[K]) => void;
    validate?: (field: FormFieldName<TData>) => void;
};

/**
 * The props of a form-fields component (the fields a create and an edit
 * page share): the page passes its `useForm()` result as `form`.
 */
export type FormFieldsProps<TData> = {
    form: FormLike<TData>;
};
