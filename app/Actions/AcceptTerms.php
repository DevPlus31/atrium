<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Modules\AuditLog;
use Illuminate\Support\Facades\DB;

final readonly class AcceptTerms
{
    /**
     * Record that the user accepted the current Terms of Service: which
     * version and when, on the account and in the audit log. Does nothing
     * while acceptance is switched off (config/privacy.php).
     */
    public function __invoke(User $user): void
    {
        $version = User::currentTermsVersion();

        if ($version === null) {
            return;
        }

        DB::transaction(static function () use ($user, $version): void {
            $user->forceFill(['terms_version' => $version, 'terms_accepted_at' => now()])->save();

            AuditLog::record(
                log: 'privacy',
                event: 'terms-accepted',
                subject: $user,
                properties: ['attributes' => ['version' => $version]],
                causer: $user,
            );
        });
    }
}
