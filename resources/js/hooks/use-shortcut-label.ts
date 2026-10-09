import { useSyncExternalStore } from 'react';

const noSubscription = () => () => {};

const isApplePlatform = (): boolean =>
    /Mac|iPhone|iPad/.test(navigator.userAgent);

/**
 * The command-palette shortcut as the platform spells it: "⌘ K" on Apple
 * devices, "Ctrl K" elsewhere. The server snapshot is "Ctrl", so server and
 * hydration render agree before the client value takes over.
 */
export function useShortcutLabel(key: string): string {
    const isApple = useSyncExternalStore(
        noSubscription,
        isApplePlatform,
        () => false,
    );

    return `${isApple ? '⌘' : 'Ctrl'} ${key}`;
}
