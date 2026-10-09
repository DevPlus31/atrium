import { useHttp } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { search } from '@/routes';

export type SearchGroup = App.Modules.Data.SearchGroupData;

type SearchResponse = { groups: SearchGroup[] };

/** Matches SearchRequest::MIN_LENGTH on the server. */
const MIN_LENGTH = 2;
const DEBOUNCE_MS = 200;

export type GlobalSearch = {
    term: string;
    setTerm: (term: string) => void;
    /** Results for the current term; empty while it is too short. */
    groups: SearchGroup[];
    searching: boolean;
    failed: boolean;
};

/**
 * The command palette's record search: a debounced standalone request
 * (Inertia's useHttp, no page visit) to every module's searcher.
 */
export function useGlobalSearch(): GlobalSearch {
    const http = useHttp<{ q: string }, SearchResponse>(search(), { q: '' });
    const [failed, setFailed] = useState(false);
    const term = http.data.q.trim();
    const { cancel, submit } = http;

    useEffect(() => {
        cancel();

        if (term.length < MIN_LENGTH) {
            return;
        }

        const timer = window.setTimeout(() => {
            submit({
                onSuccess: () => setFailed(false),
                onHttpException: () => setFailed(true),
                onNetworkError: () => setFailed(true),
            }).catch(() => {
                // Failures are reported through the callbacks above; a
                // cancelled request (the term changed) needs nothing.
            });
        }, DEBOUNCE_MS);

        return () => window.clearTimeout(timer);
    }, [term, cancel, submit]);

    const active = term.length >= MIN_LENGTH;

    return {
        term: http.data.q,
        setTerm: (value) => http.setData('q', value),
        groups: active ? (http.response?.groups ?? []) : [],
        searching: active && http.processing,
        failed: active && failed,
    };
}
