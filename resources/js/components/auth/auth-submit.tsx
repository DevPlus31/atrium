import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';

/** The full-width submit button of a sign-in page, with a spinner while it runs. */
export function AuthSubmit({
    processing,
    test,
    className,
    children,
}: {
    processing: boolean;
    /** `data-test`, for browser tests. */
    test?: string;
    className?: string;
    children: ReactNode;
}) {
    return (
        <Button
            type="submit"
            className={cn('w-full', className)}
            disabled={processing}
            data-test={test}
        >
            {processing && <Spinner />}
            {children}
        </Button>
    );
}
