import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/** The muted line shown where a list or card has nothing to show. */
export function EmptyState({
    children,
    centered = false,
    icon: Icon,
}: {
    children: ReactNode;
    /** Centred with breathing room, for a card body. */
    centered?: boolean;
    /** An icon above the line (implies centred). */
    icon?: LucideIcon;
}) {
    if (Icon !== undefined) {
        return (
            <div className="flex flex-col items-center gap-2 py-10 text-center text-muted-foreground">
                <Icon className="size-6" aria-hidden="true" />
                <p className="text-sm">{children}</p>
            </div>
        );
    }

    return (
        <p
            className={cn(
                'text-sm text-muted-foreground',
                centered && 'py-6 text-center',
            )}
        >
            {children}
        </p>
    );
}
