<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\LeaveImpersonation;
use App\Models\User;
use App\Modules\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lab404\Impersonate\Services\ImpersonateManager;

/**
 * Ends an impersonation. In the shell, not the Users module: the impersonated
 * account usually lacks the admin role, and leaving must work whatever
 * modules are installed.
 */
final readonly class LeaveImpersonationController
{
    public function __invoke(Request $request, ImpersonateManager $manager, LeaveImpersonation $action): RedirectResponse
    {
        $impersonated = $request->user();

        abort_unless($impersonated instanceof User && $manager->isImpersonating(), 403);

        $action->handle($impersonated);

        // The session belongs to another account again: drop the history the
        // impersonated user browsed so Back cannot reveal it.
        Inertia::clearHistory();
        Toast::success(__('Stopped impersonating :name.', ['name' => $impersonated->name]));

        // Back to the impersonator's own home (their first menu item).
        return to_route('dashboard');
    }
}
