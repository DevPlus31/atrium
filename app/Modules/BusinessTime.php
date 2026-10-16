<?php

declare(strict_types=1);

namespace App\Modules;

use App\Settings\BusinessSettings;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Moving between the business's clock (Settings → Business) and UTC, the
 * only zone the database stores. Read a time someone typed as business time
 * with toUtc(), show a stored instant on the business clock with fromUtc(),
 * and query a business day with dayInUtc().
 */
final readonly class BusinessTime
{
    /**
     * The business timezone, e.g. "Europe/Paris".
     */
    public static function timezone(): string
    {
        return resolve(BusinessSettings::class)->timezone;
    }

    /**
     * The current moment on the business clock.
     */
    public static function now(): CarbonImmutable
    {
        return CarbonImmutable::now(self::timezone());
    }

    /**
     * A business wall-clock time ("2026-10-16 09:00") as the UTC instant to
     * store. A string with its own offset, or a date object, keeps its
     * instant.
     */
    public static function toUtc(CarbonInterface|string $time): CarbonImmutable
    {
        $time = is_string($time) ? CarbonImmutable::parse($time, self::timezone()) : $time->toImmutable();

        return $time->utc();
    }

    /**
     * A stored instant (UTC unless the string says otherwise) on the
     * business clock.
     */
    public static function fromUtc(CarbonInterface|string $time): CarbonImmutable
    {
        $time = is_string($time) ? CarbonImmutable::parse($time, 'UTC') : $time->toImmutable();

        return $time->setTimezone(self::timezone());
    }

    /**
     * The UTC bounds of a business day (today by default), for queries such
     * as "orders placed today": `whereBetween('created_at', BusinessTime::dayInUtc())`.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public static function dayInUtc(CarbonInterface|string|null $day = null): array
    {
        $local = match (true) {
            $day === null => self::now(),
            is_string($day) => CarbonImmutable::parse($day, self::timezone()),
            default => self::fromUtc($day),
        };

        return [$local->startOfDay()->utc(), $local->endOfDay()->utc()];
    }
}
