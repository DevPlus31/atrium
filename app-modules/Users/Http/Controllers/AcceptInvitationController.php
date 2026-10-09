<?php

declare(strict_types=1);

namespace Modules\Users\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Users\Actions\AcceptInvitation;
use Modules\Users\Http\Requests\AcceptInvitationRequest;
use Modules\Users\Infrastructure\Models\Invitation;

/**
 * The invitee's side: the signed link opens a form to pick a name and a
 * password; submitting it creates the account and signs them in.
 */
final readonly class AcceptInvitationController
{
    public function show(Invitation $invitation): Response
    {
        return Inertia::render('users::public/accept-invitation', [
            'email' => $invitation->email,
            'status' => $this->status($invitation),
            'submitUrl' => $invitation->acceptUrl(),
        ]);
    }

    public function store(AcceptInvitationRequest $request, Invitation $invitation, AcceptInvitation $action): RedirectResponse
    {
        $user = $action->handle(
            $invitation,
            $request->string('name')->value(),
            $request->string('password')->value(),
        );

        Auth::login($user);
        $request->session()->regenerate();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Welcome to :app!', ['app' => Config::string('app.name')])]);

        return to_route('dashboard');
    }

    /**
     * @return 'pending'|'accepted'|'expired'|'registered'
     */
    private function status(Invitation $invitation): string
    {
        return match (true) {
            $invitation->accepted_at !== null => 'accepted',
            ! $invitation->isPending() => 'expired',
            User::query()->where('email', $invitation->email)->exists() => 'registered',
            default => 'pending',
        };
    }
}
