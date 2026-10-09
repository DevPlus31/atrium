<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Modules\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use SensitiveParameter;

final readonly class SignOutOtherSessions
{
    /**
     * End every session of the user except the current one, API tokens
     * included. Laravel rehashes the password so AuthenticateSession rejects
     * the other sessions and "remember me" cookies; with database sessions
     * their rows go too, so the sessions list is right at once.
     */
    public function handle(User $user, #[SensitiveParameter] string $password, string $currentSessionId): void
    {
        Auth::guard('web')->logoutOtherDevices($password);

        // API tokens are other sessions too.
        $user->tokens()->delete();

        if (Config::string('session.driver') === 'database') {
            $user->browserSessions()->where('id', '!=', $currentSessionId)->delete();
        }

        AuditLog::record(
            log: 'users',
            event: 'other-sessions-signed-out',
            subject: $user,
        );
    }
}
