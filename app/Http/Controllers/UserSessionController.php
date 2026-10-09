<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SignOutOtherSessions;
use App\Http\Requests\SignOutOtherSessionsRequest;
use App\Models\User;
use App\Modules\Data\SessionData;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use stdClass;

final readonly class UserSessionController
{
    public function index(Request $request, #[CurrentUser] User $user): Response
    {
        $listable = Config::string('session.driver') === 'database';

        return Inertia::render('user-sessions/index', [
            'listable' => $listable,
            'sessions' => $listable ? $this->sessions($user, $request->session()->getId()) : [],
        ]);
    }

    public function destroy(SignOutOtherSessionsRequest $request, #[CurrentUser] User $user, SignOutOtherSessions $action): RedirectResponse
    {
        $action->handle($user, $request->string('password')->value(), $request->session()->getId());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Signed out of your other sessions.')]);

        return to_route('sessions.index');
    }

    /**
     * The connection holding the sessions table (null: the default one).
     */
    private function sessionConnection(): ?string
    {
        $connection = Config::get('session.connection');

        return is_string($connection) ? $connection : null;
    }

    /**
     * The user's sessions, most recently active first.
     *
     * @return list<SessionData>
     */
    private function sessions(User $user, string $currentId): array
    {
        return array_values(DB::connection($this->sessionConnection())
            ->table(Config::string('session.table'))
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(static fn (stdClass $session): SessionData => SessionData::fromRecord(
                is_string($session->user_agent) ? $session->user_agent : null,
                is_string($session->ip_address) ? $session->ip_address : null,
                is_numeric($session->last_activity) ? (int) $session->last_activity : 0,
                $session->id === $currentId,
            ))
            ->all());
    }
}
