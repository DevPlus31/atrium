import { router } from '@inertiajs/react';
import { toast } from 'sonner';

/**
 * Toast every `Inertia::flash('toast', ...)`. Registered once at startup,
 * before Inertia boots: the first page's flash fires right after setup, before
 * any React effect could subscribe. Sonner replays toasts created before its
 * `<Toaster>` mounts, so nothing is lost.
 */
export function listenForFlashToasts(): () => void {
    return router.on('flash', (event) => {
        const data = event.detail.flash.toast;

        if (!data) {
            return;
        }

        toast[data.type](data.message);
    });
}
