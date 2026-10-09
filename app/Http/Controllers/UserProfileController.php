<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\UpdateProfile;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use App\Modules\Toast;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class UserProfileController
{
    public function edit(Request $request): Response
    {
        return Inertia::render('user-profile/edit', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function update(UpdateProfileRequest $request, #[CurrentUser] User $user, UpdateProfile $action): RedirectResponse
    {
        $action->handle($user, $request->profile());

        Toast::success(__('Profile updated.'));

        return to_route('user-profile.edit');
    }
}
