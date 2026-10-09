import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { ReactNode } from 'react';
import { EmptyValue } from '@/components/data-table';
import { Badge } from '@/components/ui/badge';

export type BadgeListProps = {
    items: string[];
    /** Show at most this many, then "+N more". */
    max?: number;
    /** Shown when there are no items (a dash by default; null for nothing). */
    empty?: ReactNode;
};

/** A wrapping row of secondary badges (roles, permissions…). */
export function BadgeList({
    items,
    max,
    empty = <EmptyValue />,
}: BadgeListProps) {
    const { tChoice } = useLaravelReactI18n();

    if (items.length === 0) {
        return empty;
    }

    const visible = max === undefined ? items : items.slice(0, max);
    const remaining = items.length - visible.length;

    return (
        <span className="flex flex-wrap items-center gap-1">
            {visible.map((item) => (
                <Badge key={item} variant="secondary">
                    {item}
                </Badge>
            ))}
            {remaining > 0 && (
                <span className="text-xs text-muted-foreground">
                    {tChoice('+:count more|+:count more', remaining)}
                </span>
            )}
        </span>
    );
}
