<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeleteAccount;
use App\Http\Requests\DeleteAccountRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * A user deleting their own account. Admins delete accounts in the Users
 * module instead.
 */
final readonly class AccountController
{
    public function destroy(DeleteAccountRequest $request, #[CurrentUser] User $user, DeleteAccount $action): RedirectResponse
    {
        $action->handle($user);

        // Not logout(): it would save the user (a fresh remember token) and
        // so bring the deleted account back.
        Auth::logoutCurrentDevice();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Inertia::clearHistory();

        return to_route('home');
    }
}
