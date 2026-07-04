import { router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useRef } from 'react';

export type TableSort = {
    field: string;
    direction: 'asc' | 'desc';
};

export type TableStateSnapshot = {
    search: string;
    filters: Record<string, string[]>;
    sort: TableSort | null;
    page: number;
    perPage: number | null;
};

export type UseTableStateReturn = TableStateSnapshot & {
    hasActiveFilters: boolean;
    setSearch: (value: string) => void;
    setFilter: (field: string, values: string[]) => void;
    setSort: (sort: TableSort | null) => void;
    toggleSort: (field: string) => void;
    setPage: (page: number) => void;
    setPerPage: (perPage: number) => void;
    reset: () => void;
};

export const PER_PAGE_OPTIONS: readonly number[] = [10, 25, 50, 100];

const MAX_PER_PAGE = 100;
const SEARCH_DEBOUNCE_MS = 400;

function parseTableState(url: string): TableStateSnapshot {
    const query = new URL(url, 'http://localhost').searchParams;

    let search = '';
    const filters: Record<string, string[]> = {};

    for (const [key, value] of query.entries()) {
        const match = /^filter\[(.+)\]$/.exec(key);

        if (!match) {
            continue;
        }

        const field = match[1];

        if (field === 'search') {
            search = value;
            continue;
        }

        filters[field] = value.split(',').filter((entry) => entry !== '');
    }

    const sortParam = query.get('sort');
    let sort: TableSort | null = null;

    if (sortParam) {
        sort = sortParam.startsWith('-')
            ? { field: sortParam.slice(1), direction: 'desc' }
            : { field: sortParam, direction: 'asc' };
    }

    const pageParam = Number(query.get('page'));
    const page = Number.isInteger(pageParam) && pageParam > 1 ? pageParam : 1;

    const perPageParam = Number(query.get('per_page'));
    const perPage =
        Number.isInteger(perPageParam) && perPageParam > 0
            ? Math.min(perPageParam, MAX_PER_PAGE)
            : null;

    return { search, filters, sort, page, perPage };
}

function buildQuery(
    state: TableStateSnapshot,
): Record<string, string | number | Record<string, string>> {
    const filter: Record<string, string> = {};

    if (state.search !== '') {
        filter.search = state.search;
    }

    for (const [field, values] of Object.entries(state.filters)) {
        if (values.length > 0) {
            filter[field] = values.join(',');
        }
    }

    const query: Record<string, string | number | Record<string, string>> = {};

    if (Object.keys(filter).length > 0) {
        query.filter = filter;
    }

    if (state.sort) {
        query.sort =
            state.sort.direction === 'desc'
                ? `-${state.sort.field}`
                : state.sort.field;
    }

    if (state.page > 1) {
        query.page = state.page;
    }

    if (state.perPage !== null) {
        query.per_page = state.perPage;
    }

    return query;
}

/**
 * URL-driven table state for server-side tables. Reads `filter[search]`,
 * `filter[<field>]` (CSV), `sort` (`field` / `-field`), `page` and
 * `per_page` from the current URL, and issues Inertia partial reloads for
 * the given prop key on every change. Search is debounced 400ms; changing
 * search, filters or sort resets the page to 1.
 */
export function useTableState(propKey: string): UseTableStateReturn {
    const page = usePage();
    const state = useMemo(() => parseTableState(page.url), [page.url]);
    const searchTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        return () => {
            if (searchTimer.current !== null) {
                clearTimeout(searchTimer.current);
            }
        };
    }, []);

    const visit = (next: TableStateSnapshot) => {
        router.get(window.location.pathname, buildQuery(next), {
            preserveState: true,
            preserveScroll: true,
            only: [propKey],
            replace: true,
        });
    };

    const setSearch = (value: string) => {
        if (searchTimer.current !== null) {
            clearTimeout(searchTimer.current);
        }

        searchTimer.current = setTimeout(() => {
            visit({ ...state, search: value, page: 1 });
        }, SEARCH_DEBOUNCE_MS);
    };

    const setFilter = (field: string, values: string[]) => {
        visit({
            ...state,
            filters: { ...state.filters, [field]: values },
            page: 1,
        });
    };

    const setSort = (sort: TableSort | null) => {
        visit({ ...state, sort, page: 1 });
    };

    const toggleSort = (field: string) => {
        let next: TableSort | null = { field, direction: 'asc' };

        if (state.sort?.field === field) {
            next =
                state.sort.direction === 'asc'
                    ? { field, direction: 'desc' }
                    : null;
        }

        setSort(next);
    };

    const setPage = (nextPage: number) => {
        visit({ ...state, page: Math.max(1, nextPage) });
    };

    const setPerPage = (perPage: number) => {
        visit({ ...state, perPage: Math.min(perPage, MAX_PER_PAGE), page: 1 });
    };

    const reset = () => {
        visit({ ...state, search: '', filters: {}, page: 1 });
    };

    const hasActiveFilters =
        state.search !== '' ||
        Object.values(state.filters).some((values) => values.length > 0);

    return {
        ...state,
        hasActiveFilters,
        setSearch,
        setFilter,
        setSort,
        toggleSort,
        setPage,
        setPerPage,
        reset,
    };
}
