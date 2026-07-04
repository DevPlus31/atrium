declare namespace App {
    namespace Modules {
        namespace Data {
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
