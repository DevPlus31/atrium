import type { ReactNode } from 'react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { cn } from '@/lib/utils';

export type SectionCardProps = {
    title: ReactNode;
    description?: ReactNode;
    /** Buttons at the end of the header. */
    actions?: ReactNode;
    contentClassName?: string;
    children: ReactNode;
};

/** A titled card section of a page (a list, an upload…). */
export function SectionCard({
    title,
    description,
    actions,
    contentClassName,
    children,
}: SectionCardProps) {
    return (
        <Card>
            <CardHeader className="flex flex-row items-start justify-between gap-4">
                <div className="grid gap-1.5">
                    <CardTitle>{title}</CardTitle>
                    {description !== undefined && (
                        <CardDescription>{description}</CardDescription>
                    )}
                </div>
                {actions !== undefined && (
                    <div className="flex shrink-0 items-center gap-2">
                        {actions}
                    </div>
                )}
            </CardHeader>
            <CardContent className={cn(contentClassName)}>
                {children}
            </CardContent>
        </Card>
    );
}

/** The column of cards a page stacks (forms, lists), at reading width. */
export function PageStack({ children }: { children: ReactNode }) {
    return <div className="grid max-w-2xl gap-6">{children}</div>;
}
