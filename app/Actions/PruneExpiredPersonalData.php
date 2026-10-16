<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ConsentRecord;
use App\Models\User;
use App\Modules\AuditLog;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

final readonly class PruneExpiredPersonalData
{
    public function __construct(private ErasePersonalData $erase)
    {
        //
    }

    /**
     * Delete what has outlived its purpose (GDPR storage limitation, the
     * CPRA's retention rule), as config('privacy.retention') sets: read
     * notifications, old consent records, and sign-ups that never verified
     * their address nor got a role. The audit log has its own retention
     * (activitylog:clean). How many of each were deleted.
     *
     * @return array{notifications: int, consent_records: int, abandoned_signups: int}
     */
    public function __invoke(): array
    {
        $pruned = DB::transaction(static fn (): array => [
            'notifications' => DatabaseNotification::query()
                ->where('read_at', '<', now()->subDays(Config::integer('privacy.retention.read_notifications_days')))
                ->toBase()
                ->delete(),
            'consent_records' => ConsentRecord::query()
                ->where('created_at', '<', now()->subDays(Config::integer('privacy.retention.consent_records_days')))
                ->toBase()
                ->delete(),
        ]);

        $pruned = [...$pruned, 'abandoned_signups' => $this->pruneAbandonedSignups()];

        if (array_sum($pruned) > 0) {
            DB::transaction(static function () use ($pruned): void {
                AuditLog::record(log: 'privacy', event: 'retention-pruned', properties: ['attributes' => $pruned]);
            });
        }

        return $pruned;
    }

    /**
     * Delete accounts that signed up, never verified their address and were
     * never given a role, once older than the configured number of days.
     */
    private function pruneAbandonedSignups(): int
    {
        $days = Config::get('privacy.retention.abandoned_signups_days');

        if (! is_int($days)) {
            return 0;
        }

        $users = User::query()
            ->whereNull('email_verified_at')
            ->where('created_at', '<', now()->subDays($days))
            ->whereDoesntHave('roles')
            ->get();

        foreach ($users as $user) {
            DB::transaction(function () use ($user): void {
                ($this->erase)($user);

                $user->delete();
            });
        }

        return $users->count();
    }
}
