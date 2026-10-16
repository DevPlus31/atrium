import type {
    AppNotification,
    Impersonation,
    LayoutConfig,
    NavItem,
} from '@/types/admin';
import type { Auth } from '@/types/auth';
import type { BreadcrumbItem } from '@/types/navigation';
import type { FlashToast } from '@/types/ui';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        layoutProps: {
            breadcrumbs: BreadcrumbItem[];
        };
        flashDataType: {
            toast?: FlashToast;
            /** Modules may flash their own keys (narrow them where read). */
            [key: string]: unknown;
        };
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            appearance: App.Enums.Appearance;
            theme: App.Enums.ThemePreset;
            layout: LayoutConfig;
            locale: string;
            timezone: string | null;
            locales: Record<string, string>;
            nav: NavItem[];
            settingsNav: NavItem[];
            impersonation: Impersonation | null;
            logo: string | null;
            supportEmail: string | null;
            announcement: App.Modules.Data.AnnouncementData | null;
            /** Cookie consent; read it through `useConsent()`. */
            consent: App.Modules.Data.ConsentData;
            /** How figures are formatted; read it through `useFormatters()`. */
            business: App.Modules.Data.BusinessData;
            unreadNotifications: number;
            /** Only after the bell asks for it (a partial reload). */
            recentNotifications?: AppNotification[];
            [key: string]: unknown;
        };
    }
}
