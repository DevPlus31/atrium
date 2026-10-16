<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AcceptTerms;
use App\Http\Requests\AcceptTermsRequest;
use App\Models\User;
use App\Modules\Toast;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Accepting the Terms of Service after they changed (EnsureTermsAccepted
 * sends users here) or when the account was created without them (by an
 * admin, or through an invitation).
 */
final readonly class TermsAcceptanceController
{
    public function create(#[CurrentUser] User $user): Response|RedirectResponse
    {
        if ($user->hasAcceptedCurrentTerms()) {
            return to_route('dashboard');
        }

        return Inertia::render('terms-acceptance/create', [
            'firstTime' => $user->terms_version === null,
        ]);
    }

    public function store(AcceptTermsRequest $request, #[CurrentUser] User $user, AcceptTerms $action): RedirectResponse
    {
        $action($user);

        Toast::success(__('Thank you. You’re all set.'));

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
