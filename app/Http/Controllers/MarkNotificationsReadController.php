<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\MarkAllNotificationsRead;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class MarkNotificationsReadController
{
    /**
     * Mark every unread notification of the user read.
     */
    public function __invoke(#[CurrentUser] User $user, MarkAllNotificationsRead $action): RedirectResponse
    {
        $action->handle($user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('All notifications marked as read.')]);

        return back();
    }
}
