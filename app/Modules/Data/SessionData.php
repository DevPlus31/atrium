<?php

declare(strict_types=1);

namespace App\Modules\Data;

use Illuminate\Support\Facades\Date;
use Spatie\LaravelData\Data;

/**
 * One signed-in device, as the sessions settings page lists it. The session
 * ID itself is a secret and never leaves the server.
 */
final class SessionData extends Data
{
    /**
     * Browser and platform names, matched in order against the user agent.
     *
     * @var array<string, string>
     */
    private const array BROWSERS = [
        'Edg/' => 'Edge',
        'OPR/' => 'Opera',
        'Firefox/' => 'Firefox',
        'Chrome/' => 'Chrome',
        'Safari/' => 'Safari',
    ];

    /**
     * @var array<string, string>
     */
    private const array PLATFORMS = [
        'iPhone' => 'iOS',
        'iPad' => 'iPadOS',
        'Android' => 'Android',
        'Windows' => 'Windows',
        'Mac OS X' => 'macOS',
        'Linux' => 'Linux',
    ];

    public function __construct(
        public ?string $browser,
        public ?string $platform,
        public bool $is_mobile,
        public ?string $ip_address,
        public bool $is_current,
        public string $last_active,
    ) {
        //
    }

    public static function fromRecord(?string $userAgent, ?string $ipAddress, int $lastActivity, bool $isCurrent): self
    {
        $agent = $userAgent ?? '';

        return new self(
            browser: self::firstMatch($agent, self::BROWSERS),
            platform: self::firstMatch($agent, self::PLATFORMS),
            is_mobile: str_contains($agent, 'Mobile') || str_contains($agent, 'Android'),
            ip_address: $ipAddress,
            is_current: $isCurrent,
            last_active: Date::createFromTimestamp($lastActivity)->toIso8601String(),
        );
    }

    /**
     * @param  array<string, string>  $names
     */
    private static function firstMatch(string $agent, array $names): ?string
    {
        foreach ($names as $needle => $name) {
            if (str_contains($agent, $needle)) {
                return $name;
            }
        }

        return null;
    }
}
