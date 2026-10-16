<?php

declare(strict_types=1);

namespace App\Modules;

use App\Enums\ConsentCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

/**
 * The browser's cookie choice, kept in the encrypted `consent` cookie: an
 * anonymous visitor ID (it links the browser to its consent records), the
 * cookie-policy version the choice was made against and the categories it
 * allowed. The matching ConsentRecord is the proof; this is the copy the
 * browser carries.
 */
final readonly class ConsentCookie
{
    public const string NAME = 'consent';

    /**
     * @param  list<string>  $granted
     */
    public function __construct(
        public string $visitorId,
        public string $version,
        public array $granted,
    ) {
        //
    }

    /**
     * The choice the request's cookie carries, or null without a readable
     * one.
     */
    public static function fromRequest(Request $request): ?self
    {
        $value = $request->cookie(self::NAME);
        $decoded = is_string($value) ? json_decode($value, true) : null;

        if (! is_array($decoded)
            || ! is_string($decoded['visitor'] ?? null)
            || ! is_string($decoded['version'] ?? null)
            || ! is_array($decoded['granted'] ?? null)) {
            return null;
        }

        return new self(
            visitorId: $decoded['visitor'],
            version: $decoded['version'],
            granted: array_values(array_filter($decoded['granted'], is_string(...))),
        );
    }

    /**
     * Whether the choice was made against the current cookie policy; an
     * older one asks the visitor again.
     */
    public function isCurrent(): bool
    {
        return $this->version === Config::string('privacy.consent.version');
    }

    public function allows(ConsentCategory $category): bool
    {
        return in_array($category->value, $this->granted, true);
    }

    /**
     * The cookie to send back, lasting config('privacy.consent.lifetime_days').
     */
    public function toCookie(): SymfonyCookie
    {
        return Cookie::make(
            self::NAME,
            (string) json_encode(['visitor' => $this->visitorId, 'version' => $this->version, 'granted' => $this->granted]),
            Config::integer('privacy.consent.lifetime_days') * 24 * 60,
            sameSite: 'lax',
        );
    }
}
