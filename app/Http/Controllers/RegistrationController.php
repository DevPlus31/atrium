<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RegisterUser;
use App\Http\Requests\RegisterUserRequest;
use App\Settings\GeneralSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Self-service sign-up, while General settings keep registration open.
 * Admins create accounts in the Users module either way.
 */
final readonly class RegistrationController
{
    public function create(GeneralSettings $settings): Response
    {
        abort_unless($settings->registration_open, 404);

        return Inertia::render('user/create');
    }

    public function store(RegisterUserRequest $request, RegisterUser $action, GeneralSettings $settings): RedirectResponse
    {
        abort_unless($settings->registration_open, 404);

        /** @var array<string, mixed> $attributes */
        $attributes = $request->safe()->except('password');

        $user = $action->handle(
            $attributes,
            $request->string('password')->value(),
        );

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
