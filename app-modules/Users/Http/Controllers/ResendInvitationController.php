<?php

declare(strict_types=1);

namespace Modules\Users\Http\Controllers;

use App\Models\User;
use App\Modules\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Modules\Users\Actions\ResendInvitation;
use Modules\Users\Infrastructure\Models\Invitation;

final readonly class ResendInvitationController
{
    #[Authorize('create', User::class)]
    public function __invoke(Invitation $invitation, ResendInvitation $action): RedirectResponse
    {
        $action->handle($invitation);

        Toast::success(__('Invitation sent again to :email.', ['email' => $invitation->email]));

        return to_route('admin.users.invitations.index');
    }
}
