import type { ReactNode } from 'react';
import Heading from '@/components/heading';

export type SettingsSectionProps = {
    title: string;
    description?: string;
    /** A paragraph under the heading, before the controls. */
    intro?: ReactNode;
    children?: ReactNode;
};

/** One titled section of an account settings page. */
export function SettingsSection({
    title,
    description,
    intro,
    children,
}: SettingsSectionProps) {
    return (
        <div className="space-y-6">
            <Heading variant="small" title={title} description={description} />
            {intro !== undefined && (
                <p className="text-sm text-muted-foreground">{intro}</p>
            )}
            {children}
        </div>
    );
}
