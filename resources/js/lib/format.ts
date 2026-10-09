/**
 * Locale-aware formatters for dates, money and counts, shared by every module
 * so the same value reads the same everywhere.
 *
 * Dates depend on the time zone: the server render has no idea of the
 * viewer's, so `useFormatters()` formats in UTC until hydration and in the
 * viewer's zone afterwards. That keeps the server HTML and the first client
 * render identical (no hydration mismatch).
 */
export type Formatters = {
    /** Minor units (cents) as currency, e.g. "$12.50". */
    money: (cents: number, currency: string) => string;
    /** A plain number with grouping, e.g. "1,234". */
    number: (value: number) => string;
    /** "Oct 8, 2026". */
    date: (value: string) => string;
    /** "October 8, 2026". */
    longDate: (value: string) => string;
    /** "Oct 8". */
    shortDate: (value: string) => string;
    /** "Oct 8, 2026, 03:04 PM". */
    dateTime: (value: string) => string;
    /** "Oct 8, 3:04 PM". */
    shortDateTime: (value: string) => string;
};

export function createFormatters(
    locale: string,
    timeZone?: string,
): Formatters {
    const dateFormat = (options: Intl.DateTimeFormatOptions) => {
        const formatter = new Intl.DateTimeFormat(locale, {
            ...options,
            timeZone,
        });

        return (value: string): string => formatter.format(new Date(value));
    };

    const numberFormatter = new Intl.NumberFormat(locale);

    return {
        money: (cents, currency) =>
            new Intl.NumberFormat(locale, {
                style: 'currency',
                currency,
            }).format(cents / 100),
        number: (value) => numberFormatter.format(value),
        date: dateFormat({ year: 'numeric', month: 'short', day: 'numeric' }),
        longDate: dateFormat({
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        }),
        shortDate: dateFormat({ month: 'short', day: 'numeric' }),
        dateTime: dateFormat({
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }),
        shortDateTime: dateFormat({
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
        }),
    };
}
