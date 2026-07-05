<?php

declare(strict_types=1);

namespace Modules\Users\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Lab404\Impersonate\Services\ImpersonateManager;

final readonly class LeaveImpersonationController
{
    public function __invoke(Request $request, ImpersonateManager $manager): RedirectResponse
    {
        $impersonated = $request->user();

        abort_unless($impersonated instanceof User && $manager->isImpersonating(), 403);

        $impersonated->leaveImpersonation();

        activity('users')
            ->performedOn($impersonated)
            ->event('impersonation-left')
            ->log('impersonation-left');

        return to_route('admin.users.index')
            ->with('success', __('Stopped impersonating :name.', ['name' => $impersonated->name]));
    }
}
