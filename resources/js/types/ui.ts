import type { ReactNode } from 'react';
import type { BreadcrumbItem } from '@/types/navigation';

export type AppLayoutProps = {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
};

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
    name?: string;
    title?: string;
    description?: string;
};
