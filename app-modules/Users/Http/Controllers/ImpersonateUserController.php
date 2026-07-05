<?php

declare(strict_types=1);

namespace Modules\Users\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Lab404\Impersonate\Services\ImpersonateManager;

#[Authorize('impersonate', 'user')]
final readonly class ImpersonateUserController
{
    public function __invoke(Request $request, ImpersonateManager $manager, User $user): RedirectResponse
    {
        $admin = $request->user();

        abort_unless($admin instanceof User && ! $manager->isImpersonating(), 403);

        if (! $admin->impersonate($user)) {
            return back()->with('error', __('Unable to impersonate :name.', ['name' => $user->name]));
        }

        activity('users')
            ->causedBy($admin)
            ->performedOn($user)
            ->event('impersonated')
            ->log('impersonated');

        return to_route('admin.dashboard.index')
            ->with('success', __('Now impersonating :name.', ['name' => $user->name]));
    }
}
