import { usePage } from '@inertiajs/react';
import { BrandMark } from '@/components/brand-mark';

/**
 * @branding The sidebar/topbar brand: the mark plus the app name, which
 * comes from APP_NAME (config/app.php) — no code change needed to rename.
 */
export default function AppLogo() {
    const { name, logo } = usePage().props;

    return (
        <>
            {logo === null ? (
                <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
                    <BrandMark className="size-5" />
                </div>
            ) : (
                <BrandMark className="size-8 rounded-md" />
            )}
            <div className="ms-1 grid flex-1 text-start text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    {name}
                </span>
            </div>
        </>
    );
}
