import { router, usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { toast } from 'sonner';
import type { Flash } from '@/types/admin';

export function useFlashToast(): void {
    useEffect(() => {
        return router.on('flash', (event) => {
            const data = event.detail.flash.toast;

            if (!data) {
                return;
            }

            toast[data.type](data.message);
        });
    }, []);
}

/**
 * Toasts the `flash` shared prop ({ success, error }) exactly once per
 * navigation. Partial reloads preserve the prop reference, so the identity
 * guard prevents stale flashes from re-toasting; a full navigation delivers
 * a fresh object and toasts again.
 */
export function useSharedFlashToast(): void {
    const flash = usePage().props.flash as Flash | undefined;
    const handled = useRef<Flash | null>(null);

    useEffect(() => {
        if (!flash || handled.current === flash) {
            return;
        }

        handled.current = flash;

        if (flash.success) {
            toast.success(flash.success);
        }

        if (flash.error) {
            toast.error(flash.error);
        }
    }, [flash]);
}
