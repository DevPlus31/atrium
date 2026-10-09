<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use SensitiveParameter;

final readonly class SignOutOtherSessions
{
    /**
     * End every session of the user except the current one, API tokens
     * included. Laravel rehashes
     * the password so AuthenticateSession rejects the other sessions and
     * "remember me" cookies; with database sessions their rows go too, so
     * the sessions list is right at once.
     */
    public function handle(User $user, #[SensitiveParameter] string $password, string $currentSessionId): void
    {
        Auth::guard('web')->logoutOtherDevices($password);

        // API tokens are other sessions too.
        $user->tokens()->delete();

        if (Config::string('session.driver') === 'database') {
            DB::connection($this->sessionConnection())
                ->table(Config::string('session.table'))
                ->where('user_id', $user->id)
                ->where('id', '!=', $currentSessionId)
                ->delete();
        }

        activity('users')
            ->performedOn($user)
            ->event('other-sessions-signed-out')
            ->log('other-sessions-signed-out');
    }

    /**
     * The connection holding the sessions table (null: the default one).
     */
    private function sessionConnection(): ?string
    {
        $connection = Config::get('session.connection');

        return is_string($connection) ? $connection : null;
    }
}
