import type { ReactNode } from 'react';

/** A success notice shown above an authentication form. */
export function AuthStatus({ children }: { children: ReactNode }) {
    return (
        <p
            role="status"
            className="mb-6 rounded-md border border-success/30 bg-success/10 px-3 py-2 text-sm font-medium text-success"
        >
            {children}
        </p>
    );
}
