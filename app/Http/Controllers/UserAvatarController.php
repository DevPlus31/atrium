<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RemoveAvatar;
use App\Actions\UpdateAvatar;
use App\Http\Requests\UpdateAvatarRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class UserAvatarController
{
    public function update(UpdateAvatarRequest $request, #[CurrentUser] User $user, UpdateAvatar $action): RedirectResponse
    {
        $action->handle($user, $request->photo());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Photo updated.')]);

        return to_route('user-profile.edit');
    }

    public function destroy(#[CurrentUser] User $user, RemoveAvatar $action): RedirectResponse
    {
        $action->handle($user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Photo removed.')]);

        return to_route('user-profile.edit');
    }
}
