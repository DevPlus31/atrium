declare namespace App {
    namespace Enums {
        export type AnnouncementLevel = 'info' | 'warning';
        export type Appearance = 'light' | 'dark' | 'system';
        export type Area = 'admin' | 'member' | 'settings';
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
            export type AnnouncementData = {
                id: string;
                message: string;
                level: App.Enums.AnnouncementLevel;
            };
            export type AuthUserData = {
                id: string;
                name: string;
                email: string;
                email_verified_at: string | null;
                avatar: string | null;
                created_at: string;
                updated_at: string;
            };
            export type ImpersonationData = {
                impersonator: string;
            };
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
            export type NotificationData = {
                id: string;
                title: string;
                body: string | null;
                url: string | null;
                action: string | null;
                read_at: string | null;
                created_at: string;
            };
            export type SearchGroupData = {
                label: string;
                icon: string | null;
                results: App.Modules.Data.SearchResultData[];
            };
            export type SearchResultData = {
                title: string;
                description: string | null;
                url: string;
            };
            export type SessionData = {
                browser: string | null;
                platform: string | null;
                is_mobile: boolean;
                ip_address: string | null;
                is_current: boolean;
                last_active: string;
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
    namespace Api {
        namespace Data {
            export type ApiTokenData = {
                id: number;
                name: string;
                abilities: string[];
                last_used_at: string | null;
                expires_at: string | null;
                created_at: string;
            };
        }
    }
    namespace Audit {
        namespace Data {
            export type AccountActivityWidgetData = {
                entries: {
                    id: string;
                    event: string | null;
                    causer: string | null;
                    created_at: string;
                }[];
            };
            export type ActivityData = {
                id: string;
                log_name: string | null;
                event: string | null;
                description: string;
                causer: {
                    name: string;
                    email: string;
                } | null;
                subject_type: string | null;
                subject_id: string | null;
                changes: Record<string, unknown>;
                created_at: string;
            };
        }
    }
    namespace Catalog {
        namespace Data {
            export type ProductData = {
                id: string;
                name: string;
                sku: string;
                price_cents: number;
                currency: string;
                description: string | null;
                published_at: string | null;
                created_at: string;
                can: {
                    update: boolean;
                    delete: boolean;
                    publish: boolean;
                };
            };
        }
    }
    namespace Dashboard {
        namespace Data {
            export type AccessStatusWidgetData = {
                email: string;
            };
            export type AccountOverviewWidgetData = {
                email_verified: boolean;
                two_factor_enabled: boolean;
                passkeys: number;
                member_since: string;
            };
            export type WidgetDescriptorData = {
                key: string;
                prop: string;
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
    namespace Settings {
        namespace Data {
            export type AnnouncementSettingsData = {
                message: string | null;
                level: App.Enums.AnnouncementLevel;
                ends_at: string | null;
                is_active: boolean;
            };
            export type GeneralSettingsData = {
                logo: string | null;
                support_email: string | null;
                registration_open: boolean;
            };
        }
    }
    namespace Shop {
        namespace Data {
            export type MemberOrdersWidgetData = {
                total: number;
                orders: {
                    id: string;
                    number: string;
                    status: Modules.Shop.Domain.Enums.OrderStatus;
                    total_cents: number;
                    currency: string;
                    placed_at: string;
                }[];
            };
            export type OrderData = {
                id: string;
                number: string;
                customer_email: string;
                status: Modules.Shop.Domain.Enums.OrderStatus;
                total_cents: number;
                currency: string;
                paid_at: string | null;
                shipped_at: string | null;
                cancelled_at: string | null;
                created_at: string;
                can: {
                    update: boolean;
                    delete: boolean;
                };
                transitions: Modules.Shop.Domain.Enums.OrderStatus[];
            };
        }
        namespace Domain {
            namespace Enums {
                export type OrderStatus =
                    | 'pending'
                    | 'paid'
                    | 'shipped'
                    | 'cancelled';
            }
        }
    }
    namespace Users {
        namespace Data {
            export type InvitationData = {
                id: string;
                email: string;
                roles: string[];
                invited_by: string | null;
                expires_at: string;
                created_at: string;
            };
            export type RecentUsersWidgetData = {
                users: {
                    id: string;
                    name: string;
                    email: string;
                    avatar: string | null;
                    created_at: string;
                }[];
            };
            export type UserData = {
                id: string;
                name: string;
                email: string;
                email_verified_at: string | null;
                avatar: string | null;
                roles: string[];
                created_at: string;
                can: {
                    update: boolean;
                    delete: boolean;
                    impersonate: boolean;
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
