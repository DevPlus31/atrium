<?php

declare(strict_types=1);

namespace Modules\Users\Http\Controllers;

use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Users\Actions\InviteUser;
use Modules\Users\Actions\RevokeInvitation;
use Modules\Users\Data\InvitationData;
use Modules\Users\Http\Requests\StoreInvitationRequest;
use Modules\Users\Infrastructure\Models\Invitation;
use Spatie\Permission\Models\Role;

#[Authorize('create', User::class)]
final readonly class InvitationController
{
    private const int PER_PAGE = 20;

    public function index(): Response
    {
        return Inertia::render('users::invitations', [
            'invitations' => Inertia::scroll(fn () => Invitation::pending()
                ->with('inviter')
                ->latest()
                ->paginate(self::PER_PAGE)
                ->through(InvitationData::fromModel(...))),
            'roles' => Role::query()->orderBy('name')->pluck('name')->values()->all(),
            'lifetimeDays' => Invitation::LIFETIME_DAYS,
        ]);
    }

    public function store(StoreInvitationRequest $request, #[CurrentUser] User $user, InviteUser $action): RedirectResponse
    {
        $action->handle($user, $request->email(), $request->roles());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation sent.')]);

        return to_route('admin.users.invitations.index');
    }

    public function destroy(Invitation $invitation, RevokeInvitation $action): RedirectResponse
    {
        $action->handle($invitation);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation revoked.')]);

        return to_route('admin.users.invitations.index');
    }
}
