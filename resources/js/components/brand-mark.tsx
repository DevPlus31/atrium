import { usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { cn } from '@/lib/utils';

/**
 * @branding The product's mark: the logo uploaded in Settings → General, or
 * the built-in AppLogoIcon until one is uploaded.
 */
export function BrandMark({ className }: { className?: string }) {
    const { logo } = usePage().props;

    if (logo !== null) {
        return (
            <img
                src={logo}
                alt=""
                className={cn('object-contain', className)}
            />
        );
    }

    return <AppLogoIcon className={cn('fill-current', className)} />;
}
