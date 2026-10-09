<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RemoveAvatar;
use App\Actions\UpdateAvatar;
use App\Http\Requests\UpdateAvatarRequest;
use App\Models\User;
use App\Modules\Toast;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class UserAvatarController
{
    public function update(UpdateAvatarRequest $request, #[CurrentUser] User $user, UpdateAvatar $action): RedirectResponse
    {
        $action->handle($user, $request->photo());

        Toast::success(__('Photo updated.'));

        return to_route('user-profile.edit');
    }

    public function destroy(#[CurrentUser] User $user, RemoveAvatar $action): RedirectResponse
    {
        $action->handle($user);

        Toast::success(__('Photo removed.'));

        return to_route('user-profile.edit');
    }
}
