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
