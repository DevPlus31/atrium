/**
 * Admin shared-prop contracts.
 *
 * Server-shaped types re-export the generated DTO types
 * (resources/js/types/generated.d.ts, `php artisan typescript:transform`);
 * the rest are client-side contracts consumed by the shell and data-table.
 */

export type NavItem = App.Modules.Data.NavItemData;

export type Flash = {
    success: string | null;
    error: string | null;
};

export type LayoutConfig = App.Modules.Data.LayoutConfigData;

export type Impersonation = {
    impersonator: string;
};

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
