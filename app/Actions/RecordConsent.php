<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ConsentCategory;
use App\Models\ConsentRecord;
use App\Models\User;
use App\Modules\ConsentCookie;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use NoDiscard;

final readonly class RecordConsent
{
    /**
     * Record a visitor's cookie choice and return the cookie their browser
     * keeps. The consent record is itself the proof of consent (GDPR
     * Art. 7(1)), so it is not repeated in the audit log. It stores the
     * choice as made; ResolveConsent applies a Global Privacy Control signal
     * on top.
     *
     * @param  list<ConsentCategory>  $granted
     */
    #[NoDiscard]
    public function __invoke(?string $visitorId, ?User $user, array $granted, bool $gpc): ConsentCookie
    {
        $cookie = new ConsentCookie(
            visitorId: is_string($visitorId) && Str::isUuid($visitorId) ? $visitorId : (string) Str::uuid(),
            version: Config::string('privacy.consent.version'),
            granted: array_map(static fn (ConsentCategory $category): string => $category->value, $granted),
        );

        DB::transaction(static function () use ($cookie, $user, $gpc): void {
            ConsentRecord::query()->create([
                'visitor_id' => $cookie->visitorId,
                'user_id' => $user?->id,
                'version' => $cookie->version,
                'granted' => $cookie->granted,
                'gpc' => $gpc,
                'created_at' => now(),
            ]);
        });

        return $cookie;
    }
}
