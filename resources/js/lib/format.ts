/**
 * Locale-aware formatters for dates, money, percentages, distances and
 * counts, shared by every module so the same value reads the same
 * everywhere.
 *
 * Dates follow the viewer: their language and time zone. The server render
 * has no idea of the viewer's zone, so `useFormatters()` formats in UTC
 * until hydration and in the viewer's zone afterwards. That keeps the server
 * HTML and the first client render identical (no hydration mismatch).
 *
 * Figures follow the business (Settings → Business): its regional format,
 * currency and distance unit, so an amount reads the same for everyone.
 */
export type Formatters = {
    /**
     * Minor units (cents) as currency, e.g. "$12.50"; in the business
     * currency unless another is given. Currencies without decimals (JPY)
     * are handled.
     */
    money: (minorUnits: number, currency?: string) => string;
    /** A ratio as a percentage: 0.125 → "12.5%". */
    percent: (ratio: number, maximumFractionDigits?: number) => string;
    /** Kilometres in the business distance unit: 12.3 → "12.3 km" or "7.6 mi". */
    distance: (kilometers: number, maximumFractionDigits?: number) => string;
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

/** How the business formats figures (the `business` shared prop). */
export type BusinessFormat = Pick<
    App.Modules.Data.BusinessData,
    'locale' | 'currency' | 'distance_unit'
>;

const KILOMETERS_PER_MILE = 1.609344;

export function createFormatters(
    locale: string,
    timeZone: string | undefined,
    business: BusinessFormat,
): Formatters {
    const dateFormat = (options: Intl.DateTimeFormatOptions) => {
        const formatter = new Intl.DateTimeFormat(locale, {
            ...options,
            timeZone,
        });

        return (value: string): string => formatter.format(new Date(value));
    };

    const numberFormatter = new Intl.NumberFormat(business.locale);

    return {
        money: (minorUnits, currency = business.currency) => {
            const formatter = new Intl.NumberFormat(business.locale, {
                style: 'currency',
                currency,
            });
            const digits =
                formatter.resolvedOptions().maximumFractionDigits ?? 2;

            return formatter.format(minorUnits / 10 ** digits);
        },
        percent: (ratio, maximumFractionDigits = 1) =>
            new Intl.NumberFormat(business.locale, {
                style: 'percent',
                maximumFractionDigits,
            }).format(ratio),
        distance: (kilometers, maximumFractionDigits = 1) =>
            new Intl.NumberFormat(business.locale, {
                style: 'unit',
                unit: business.distance_unit === 'mi' ? 'mile' : 'kilometer',
                maximumFractionDigits,
            }).format(
                business.distance_unit === 'mi'
                    ? kilometers / KILOMETERS_PER_MILE
                    : kilometers,
            ),
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

/** The wall-clock parts of an instant in a time zone (the viewer's if none). */
const wallClock = (instant: number, timeZone?: string): number => {
    const parts = Object.fromEntries(
        new Intl.DateTimeFormat('en-US', {
            timeZone,
            hourCycle: 'h23',
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        })
            .formatToParts(new Date(instant))
            .map((part) => [part.type, Number(part.value)]),
    );

    return Date.UTC(
        parts.year,
        parts.month - 1,
        parts.day,
        parts.hour,
        parts.minute,
        parts.second,
    );
};

/**
 * An ISO instant as the value of a datetime-local input, in the given time
 * zone (the user's preference) rather than the browser's.
 */
export function toZonedInput(iso: string | null, timeZone?: string): string {
    if (iso === null) {
        return '';
    }

    return new Date(wallClock(new Date(iso).getTime(), timeZone))
        .toISOString()
        .slice(0, 16);
}

/**
 * A datetime-local value read as wall-clock time in the given time zone,
 * back to an ISO instant. The zone's offset is taken at the result itself,
 * so dates on the other side of a DST change come out right.
 */
export function fromZonedInput(value: string, timeZone?: string): string {
    const asUtc = new Date(`${value}:00Z`).getTime();
    let instant = asUtc - (wallClock(asUtc, timeZone) - asUtc);
    instant = asUtc - (wallClock(instant, timeZone) - instant);

    return new Date(instant).toISOString();
}

/**
 * A wall-clock time at the business ("2026-10-16T09:00", a datetime-local
 * value) as the UTC instant to send to the server: the client twin of
 * `BusinessTime::toUtc()`.
 */
export function businessToUtc(value: string, businessTimeZone: string): string {
    return fromZonedInput(value, businessTimeZone);
}

/**
 * A stored UTC instant as a datetime-local value on the business clock: the
 * client twin of `BusinessTime::fromUtc()`.
 */
export function utcToBusiness(
    iso: string | null,
    businessTimeZone: string,
): string {
    return toZonedInput(iso, businessTimeZone);
}
