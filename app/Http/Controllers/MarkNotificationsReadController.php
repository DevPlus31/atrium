<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\MarkAllNotificationsRead;
use App\Models\User;
use App\Modules\Toast;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class MarkNotificationsReadController
{
    /**
     * Mark every unread notification of the user read.
     */
    public function __invoke(#[CurrentUser] User $user, MarkAllNotificationsRead $action): RedirectResponse
    {
        $action->handle($user);

        Toast::success(__('All notifications marked as read.'));

        return back();
    }
}
