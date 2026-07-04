import type { Flash, LayoutConfig, NavItem } from '@/types/admin';
import type { Auth } from '@/types/auth';
import type { FlashToast } from '@/types/ui';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        flashDataType: {
            toast?: FlashToast;
        };
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            appearance: App.Enums.Appearance;
            theme: App.Enums.ThemePreset;
            layout: LayoutConfig;
            nav: NavItem[];
            flash: Flash;
            [key: string]: unknown;
        };
    }
}
