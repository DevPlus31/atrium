<?php

declare(strict_types=1);

namespace App\Modules;

use Illuminate\Database\Eloquent\Model;

/**
 * Write one audit-log entry (spatie/laravel-activitylog), the way every
 * action does: the log is the module (or "users", "settings"), the event a
 * kebab-case slug the Audit page translates, and the description the same
 * slug unless one is given. Call it inside the action's transaction.
 */
final readonly class AuditLog
{
    /**
     * @param  array<string, mixed>  $properties  usually `['attributes' => …]`, plus `'old'` on updates
     */
    public static function record(
        string $log,
        string $event,
        ?Model $subject = null,
        array $properties = [],
        ?Model $causer = null,
        ?string $description = null,
    ): void {
        $logger = activity($log)->event($event)->withProperties($properties);

        if ($subject instanceof Model) {
            $logger->performedOn($subject);
        }

        if ($causer instanceof Model) {
            $logger->causedBy($causer);
        }

        $logger->log($description ?? $event);
    }
}
