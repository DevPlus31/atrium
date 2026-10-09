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
     * Update any subset of {appearance, theme, layout, locale, timezone} for
     * the current user and re-issue the js-readable cookies of the first
     * four (the timezone lives on the account only), so guests-turned-users
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

        $cookies = [
            'appearance' => $preferences['appearance']->value,
            'theme' => $preferences['theme']->value,
            'layout' => json_encode($preferences['layout'], JSON_THROW_ON_ERROR),
            'locale' => $preferences['locale'],
        ];

        foreach ($cookies as $name => $value) {
            Cookie::queue(Cookie::forever(name: $name, value: $value, httpOnly: false, sameSite: 'lax'));
        }

        return back();
    }
}
