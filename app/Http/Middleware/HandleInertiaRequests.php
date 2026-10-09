<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\ResolveUserPreferences;
use App\Enums\Area;
use App\Models\User;
use App\Modules\Data\AnnouncementData;
use App\Modules\Data\AuthUserData;
use App\Modules\Data\ImpersonationData;
use App\Modules\Data\NotificationData;
use App\Modules\NavRegistry;
use App\Settings\AnnouncementSettings;
use App\Settings\GeneralSettings;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Middleware;
use Lab404\Impersonate\Services\ImpersonateManager;

final class HandleInertiaRequests extends Middleware
{
    private const int RECENT_NOTIFICATIONS = 8;

    /**
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    public function __construct(
        private readonly NavRegistry $nav,
        private readonly ResolveUserPreferences $preferences,
        private readonly ImpersonateManager $impersonate,
        private readonly GeneralSettings $settings,
        private readonly AnnouncementSettings $announcement,
    ) {
        //
    }

    /**
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $preferences = $this->preferences->handle($request);

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user instanceof User ? AuthUserData::fromModel($user) : null,
            ],
            'appearance' => $preferences['appearance']->value,
            'theme' => $preferences['theme']->value,
            'layout' => $preferences['layout'],
            'locale' => $preferences['locale'],
            'timezone' => $preferences['timezone'],
            'locales' => config('app.available_locales'),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'nav' => $user instanceof User ? $this->nav->itemsFor($user, Area::for($user)) : [],
            'settingsNav' => $user instanceof User ? $this->nav->itemsFor($user, Area::Settings) : [],
            'impersonation' => $this->impersonation(),
            'logo' => $this->settings->logoUrl(),
            'supportEmail' => $this->settings->support_email,
            'announcement' => $this->announcement($request),
            'unreadNotifications' => fn (): int => $user instanceof User ? $user->unreadNotifications()->count() : 0,
            // Loaded only when the bell opens (a partial reload asks for it).
            'recentNotifications' => Inertia::optional(fn (): array => $user instanceof User ? $this->recentNotifications($user) : []),
        ];
    }

    /**
     * The active announcement, unless this browser dismissed this version.
     */
    private function announcement(Request $request): ?AnnouncementData
    {
        if (! $this->announcement->isActive() || $request->cookie(AnnouncementSettings::DISMISSED_COOKIE) === $this->announcement->version()) {
            return null;
        }

        return AnnouncementData::fromSettings($this->announcement);
    }

    /**
     * @return list<NotificationData>
     */
    private function recentNotifications(User $user): array
    {
        return array_values($user->notifications()
            ->limit(self::RECENT_NOTIFICATIONS)
            ->get()
            ->map(NotificationData::fromModel(...))
            ->all());
    }

    /**
     * The minimal impersonation state the admin shell banner needs.
     */
    private function impersonation(): ?ImpersonationData
    {
        if (! $this->impersonate->isImpersonating()) {
            return null;
        }

        $impersonator = $this->impersonate->getImpersonator();

        return $impersonator instanceof User ? new ImpersonationData(impersonator: $impersonator->name) : null;
    }
}
