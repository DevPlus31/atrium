import type { InertiaLinkProps } from '@inertiajs/react';
import type { ClassValue } from 'clsx';
import { clsx } from 'clsx';
import type { FormEvent } from 'react';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}

/**
 * A form's onSubmit: stop the browser's own submission and run `submit`
 * (usually `form.submit(...)`).
 */
export function handleSubmit(submit: () => void): (event: FormEvent) => void {
    return (event) => {
        event.preventDefault();
        submit();
    };
}
