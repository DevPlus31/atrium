import { Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { cn, handleSubmit } from '@/lib/utils';

export type FormCardProps = {
    title: string;
    /** Next to the title (e.g. a status badge). */
    badge?: ReactNode;
    description?: ReactNode;
    /** Called on submit; the default browser submission is already prevented. */
    onSubmit: () => void;
    processing: boolean;
    submitLabel: string;
    /** Shown instead of the spinner while idle (e.g. a send icon). */
    submitIcon?: ReactNode;
    /** `data-test` of the submit button, for browser tests. */
    submitTest?: string;
    /** Adds a "Cancel" link back to this page. */
    cancelHref?: NonNullable<InertiaLinkProps['href']>;
    /** More buttons after submit and cancel (e.g. "Remove"). */
    actions?: ReactNode;
    /** Set to "" inside a page that already limits the width. */
    className?: string;
    children: ReactNode;
};

/**
 * The single-card form every create, edit and settings page uses: a titled
 * card, the fields, and a submit button with a spinner while it runs.
 */
export function FormCard({
    title,
    badge,
    description,
    onSubmit,
    processing,
    submitLabel,
    submitIcon,
    submitTest,
    cancelHref,
    actions,
    className = 'max-w-2xl',
    children,
}: FormCardProps) {
    const { t } = useLaravelReactI18n();

    return (
        <Card className={cn(className)}>
            <CardHeader>
                <div className="flex items-center gap-2">
                    <CardTitle>{title}</CardTitle>
                    {badge}
                </div>
                {description && (
                    <CardDescription>{description}</CardDescription>
                )}
            </CardHeader>
            <CardContent>
                <form onSubmit={handleSubmit(onSubmit)} className="grid gap-6">
                    {children}

                    <div className="flex flex-wrap items-center gap-2">
                        <Button
                            type="submit"
                            disabled={processing}
                            data-test={submitTest}
                        >
                            {processing ? <Spinner /> : submitIcon}
                            {submitLabel}
                        </Button>
                        {cancelHref !== undefined && (
                            <Button variant="ghost" asChild>
                                <Link href={cancelHref}>{t('Cancel')}</Link>
                            </Button>
                        )}
                        {actions}
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}
