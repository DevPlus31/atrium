import { router, usePage } from '@inertiajs/react';
import { VenetianMask } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { leave } from '@/routes/impersonation';

/**
 * Full-width warning bar shown while an admin impersonates another user.
 * Renders nothing when no impersonation is active.
 */
export function ImpersonationBanner() {
    const { auth, impersonation } = usePage().props;

    if (impersonation === null) {
        return null;
    }

    return (
        <div className="flex w-full shrink-0 items-center justify-between gap-2 border-b border-destructive/20 bg-destructive/10 px-4 py-1.5 text-sm text-destructive">
            <span className="flex items-center gap-2">
                <VenetianMask aria-hidden className="size-4 shrink-0" />
                <span>
                    Impersonating{' '}
                    <span className="font-medium">{auth.user.name}</span> —
                    acting as this user.
                </span>
            </span>
            <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() => router.post(leave.url())}
            >
                Leave — back to {impersonation.impersonator}
            </Button>
        </div>
    );
}
