import type { ReactNode } from 'react';

/** The list styling, for a list rendered by another element (InfiniteScroll). */
export const itemListClassName = 'divide-y rounded-lg border';

/** A bordered list of records with per-row actions (tokens, passkeys…). */
export function ItemList({ children }: { children: ReactNode }) {
    return <ul className={itemListClassName}>{children}</ul>;
}

/** A details line given as parts; empty parts are dropped. */
export type ItemDetails = ReactNode | Array<string | false | null | undefined>;

/**
 * One record: an optional icon, the name, a line of details (parts joined
 * with " · "), anything extra (badges), and the row's buttons at the end.
 */
export function ItemRow({
    icon,
    title,
    details,
    extra,
    actions,
    test,
}: {
    icon?: ReactNode;
    title: ReactNode;
    details?: ItemDetails;
    extra?: ReactNode;
    actions?: ReactNode;
    /** `data-test` of the row, for browser tests. */
    test?: string;
}) {
    return (
        <li className="flex flex-wrap items-center gap-3 p-3" data-test={test}>
            {icon}
            <div className="grid min-w-0 flex-1 gap-1">
                <span className="truncate text-sm font-medium">{title}</span>
                {details !== undefined && (
                    <span className="truncate text-xs text-muted-foreground">
                        {Array.isArray(details)
                            ? details.filter(Boolean).join(' · ')
                            : details}
                    </span>
                )}
                {extra}
            </div>
            {actions !== undefined && (
                <div className="flex gap-1">{actions}</div>
            )}
        </li>
    );
}
