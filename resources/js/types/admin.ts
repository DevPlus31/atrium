/**
 * Admin shared-prop contracts.
 *
 * Server-shaped types re-export the generated DTO types
 * (resources/js/types/generated.d.ts, `php artisan typescript:transform`);
 * the rest are client-side contracts consumed by the shell and data-table.
 */

export type NavItem = App.Modules.Data.NavItemData;

export type LayoutConfig = App.Modules.Data.LayoutConfigData;

export type Impersonation = App.Modules.Data.ImpersonationData;

export type AppNotification = App.Modules.Data.NotificationData;

/**
 * The `meta` block of a spatie/laravel-data PaginatedDataCollection. Generic
 * containers cannot be generated, so this mirrors the serializer's shape.
 */
export type PaginationMeta = {
    current_page: number;
    from: number | null;
    last_page: number;
    per_page: number;
    to: number | null;
    total: number;
};

export interface Paginated<T> {
    data: T[];
    meta: PaginationMeta;
}
