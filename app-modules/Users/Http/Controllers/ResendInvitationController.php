<?php

declare(strict_types=1);

namespace Modules\Users\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Modules\Users\Actions\ResendInvitation;
use Modules\Users\Infrastructure\Models\Invitation;

final readonly class ResendInvitationController
{
    #[Authorize('create', User::class)]
    public function __invoke(Invitation $invitation, ResendInvitation $action): RedirectResponse
    {
        abort_if($invitation->accepted_at !== null, 404);

        $action->handle($invitation);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation sent again to :email.', ['email' => $invitation->email])]);

        return to_route('admin.users.invitations.index');
    }
}
