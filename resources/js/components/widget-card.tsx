import type { ReactNode } from 'react';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

/**
 * A dashboard card: a small kicker over a title (or, with `stat`, a big
 * number), the widget's content, and an optional footnote.
 */
export function WidgetCard({
    kicker,
    title,
    stat = false,
    footer,
    children,
}: {
    kicker: string;
    title: ReactNode;
    stat?: boolean;
    footer?: ReactNode;
    children: ReactNode;
}) {
    return (
        <Card>
            <CardHeader>
                <CardDescription>{kicker}</CardDescription>
                <CardTitle
                    className={stat ? 'text-3xl tabular-nums' : 'text-base'}
                >
                    {title}
                </CardTitle>
            </CardHeader>
            <CardContent>{children}</CardContent>
            {footer !== undefined && (
                <CardFooter className="text-xs text-muted-foreground">
                    {footer}
                </CardFooter>
            )}
        </Card>
    );
}
