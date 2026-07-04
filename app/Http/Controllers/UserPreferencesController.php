<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ResolveUserPreferences;
use App\Actions\UpdateUserPreferences;
use App\Http\Requests\UpdateUserPreferencesRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;

final readonly class UserPreferencesController
{
    /**
     * Update any subset of {appearance, theme, layout} for the current user
     * and re-issue the matching js-readable cookies so guests-turned-users
     * and first paints stay consistent.
     */
    public function __invoke(
        UpdateUserPreferencesRequest $request,
        #[CurrentUser] User $user,
        UpdateUserPreferences $update,
        ResolveUserPreferences $resolve,
    ): RedirectResponse {
        $update->handle($user, $request->validated());

        $preferences = $resolve->handle($request);

        Cookie::queue(Cookie::forever(
            name: 'appearance',
            value: $preferences['appearance']->value,
            httpOnly: false,
            sameSite: 'lax',
        ));
        Cookie::queue(Cookie::forever(
            name: 'theme',
            value: $preferences['theme']->value,
            httpOnly: false,
            sameSite: 'lax',
        ));
        Cookie::queue(Cookie::forever(
            name: 'layout',
            value: json_encode($preferences['layout'], JSON_THROW_ON_ERROR),
            httpOnly: false,
            sameSite: 'lax',
        ));

        return back();
    }
}
