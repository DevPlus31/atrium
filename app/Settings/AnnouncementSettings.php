<?php

declare(strict_types=1);

namespace App\Settings;

use App\Enums\AnnouncementLevel;
use Carbon\CarbonImmutable;
use Spatie\LaravelSettings\Settings;

/**
 * The site-wide notice an administrator writes in Settings → Announcement;
 * the shell shows it at the top of every page until it ends or the viewer
 * dismisses it.
 */
final class AnnouncementSettings extends Settings
{
    /**
     * The cookie naming the version a browser dismissed.
     */
    public const string DISMISSED_COOKIE = 'announcement_dismissed';

    /**
     * Plain text; null means no announcement.
     */
    public ?string $message = null;

    public AnnouncementLevel $level = AnnouncementLevel::Info;

    /**
     * When it stops showing (ISO 8601); null keeps it until removed.
     */
    public ?string $ends_at = null;

    public static function group(): string
    {
        return 'announcement';
    }

    public function isActive(): bool
    {
        return $this->message !== null
            && ($this->ends_at === null || CarbonImmutable::parse($this->ends_at)->isFuture());
    }

    /**
     * Changes with the text, level or end, so a dismissed notice comes back
     * once it says something new.
     */
    public function version(): string
    {
        return mb_substr(hash('sha256', implode('|', [$this->message, $this->level->value, $this->ends_at])), 0, 16);
    }
}
