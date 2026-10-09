<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\UpdateNotificationPreferences;
use App\Http\Requests\UpdateNotificationPreferencesRequest;
use App\Models\User;
use App\Modules\Toast;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class UserNotificationPreferencesController
{
    public function edit(#[CurrentUser] User $user): Response
    {
        return Inertia::render('user-notification-preferences/edit', [
            'notifyByEmail' => $user->notify_by_email,
        ]);
    }

    public function update(UpdateNotificationPreferencesRequest $request, #[CurrentUser] User $user, UpdateNotificationPreferences $action): RedirectResponse
    {
        $action->handle($user, $request->notifyByEmail());

        Toast::success(__('Notification settings saved.'));

        return to_route('notification-preferences.edit');
    }
}
