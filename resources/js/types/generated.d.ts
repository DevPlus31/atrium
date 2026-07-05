declare namespace App {
    namespace Enums {
        export type Appearance = 'light' | 'dark' | 'system';
        export type ContentWidth = 'fluid' | 'boxed';
        export type Direction = 'ltr' | 'rtl';
        export type HeaderMode = 'sticky' | 'static';
        export type NavPlacement = 'sidebar-left' | 'sidebar-right' | 'topbar';
        export type SidebarCollapsible = 'offcanvas' | 'icon' | 'none';
        export type SidebarVariant = 'sidebar' | 'floating' | 'inset';
        export type ThemePreset = 'default' | 'ember' | 'contrast';
    }
    namespace Modules {
        namespace Data {
            export type LayoutConfigData = {
                nav_placement: App.Enums.NavPlacement;
                sidebar_variant: App.Enums.SidebarVariant;
                sidebar_collapsible: App.Enums.SidebarCollapsible;
                content_width: App.Enums.ContentWidth;
                header: App.Enums.HeaderMode;
                direction: App.Enums.Direction;
            };
            export type NavItemData = {
                label: string;
                routeName: string;
                href: string;
                icon: string | null;
                group: string | null;
                sort: number;
                external: boolean;
            };
        }
    }
}
declare namespace Illuminate {
    export type CursorPaginator<TKey, TValue> = {
        data: TKey extends string ? Record<TKey, TValue> : TValue[];
        links: {
            url: string | null;
            label: string;
            active: boolean;
        }[];
        meta: {
            path: string;
            per_page: number;
            next_cursor: string | null;
            next_page_url: string | null;
            prev_cursor: string | null;
            prev_page_url: string | null;
        };
    };
    export type CursorPaginatorInterface<TKey, TValue> =
        Illuminate.CursorPaginator<TKey, TValue>;
    export type LengthAwarePaginator<TKey, TValue> = {
        data: TKey extends string ? Record<TKey, TValue> : TValue[];
        links: {
            url: string | null;
            label: string;
            active: boolean;
        }[];
        meta: {
            total: number;
            current_page: number;
            first_page_url: string;
            from: number | null;
            last_page: number;
            last_page_url: string;
            next_page_url: string | null;
            path: string;
            per_page: number;
            prev_page_url: string | null;
            to: number | null;
        };
    };
    export type LengthAwarePaginatorInterface<TKey, TValue> =
        Illuminate.LengthAwarePaginator<TKey, TValue>;
}
declare namespace Modules {
    namespace Dashboard {
        namespace Data {
            export type WidgetDescriptorData = {
                key: string;
                sort: number;
            };
        }
    }
    namespace Roles {
        namespace Data {
            export type RoleData = {
                id: string;
                name: string;
                permissions: string[];
                users_count: number;
                is_system: boolean;
                created_at: string;
                can: {
                    update: boolean;
                    delete: boolean;
                };
            };
        }
    }
    namespace Users {
        namespace Data {
            export type RecentUsersWidgetData = {
                users: {
                    id: string;
                    name: string;
                    email: string;
                    created_at: string;
                }[];
            };
            export type UserData = {
                id: string;
                name: string;
                email: string;
                email_verified_at: string | null;
                roles: string[];
                created_at: string;
                can: {
                    update: boolean;
                    delete: boolean;
                };
            };
            export type UsersTotalWidgetData = {
                total: number;
                series: {
                    date: string;
                    count: number;
                }[];
            };
        }
    }
}
declare namespace Spatie {
    namespace LaravelData {
        export type CursorPaginatedDataCollection<TKey, TValue> =
            Illuminate.CursorPaginator<TKey, TValue>;
        export type PaginatedDataCollection<TKey, TValue> =
            Illuminate.LengthAwarePaginator<TKey, TValue>;
    }
}
