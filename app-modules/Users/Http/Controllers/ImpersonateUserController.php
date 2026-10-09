<?php

declare(strict_types=1);

namespace Modules\Users\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Lab404\Impersonate\Services\ImpersonateManager;
use Modules\Users\Actions\ImpersonateUser;

#[Authorize('impersonate', 'user')]
final readonly class ImpersonateUserController
{
    public function __invoke(Request $request, ImpersonateManager $manager, User $user, ImpersonateUser $action): RedirectResponse
    {
        $admin = $request->user();

        abort_unless($admin instanceof User && ! $manager->isImpersonating(), 403);

        if (! $action->handle($admin, $user)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Unable to impersonate :name.', ['name' => $user->name])]);

            return back();
        }

        // The admin's pages stay out of the impersonated session's history.
        Inertia::clearHistory();
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Now impersonating :name.', ['name' => $user->name])]);

        // Impersonation targets usually lack panel access, so land on a page
        // every account can open; its shell carries the leave banner.
        return to_route('user-profile.edit');
    }
}
