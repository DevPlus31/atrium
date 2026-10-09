<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\MarkNotificationRead;
use App\Models\User;
use App\Modules\Data\NotificationData;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class UserNotificationController
{
    private const int PER_PAGE = 20;

    public function index(#[CurrentUser] User $user): Response
    {
        return Inertia::render('user-notifications/index', [
            'notifications' => Inertia::scroll(fn () => $user->notifications()
                ->paginate(self::PER_PAGE)
                ->through(NotificationData::fromModel(...))),
        ]);
    }

    /**
     * Mark one of the user's notifications read, then follow its link when
     * it points into this app. Another user's notification is a 404.
     */
    public function update(Request $request, #[CurrentUser] User $user, string $notification, MarkNotificationRead $action): RedirectResponse
    {
        $notification = $user->notifications()->findOrFail($notification);
        $action->handle($notification);

        $url = $notification->data['url'] ?? null;

        return is_string($url) && parse_url($url, PHP_URL_HOST) === $request->getHost()
            ? redirect()->to($url)
            : back();
    }
}
