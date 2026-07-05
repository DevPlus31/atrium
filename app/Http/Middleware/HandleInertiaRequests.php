<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\ResolveUserPreferences;
use App\Models\User;
use App\Modules\NavRegistry;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Lab404\Impersonate\Services\ImpersonateManager;

final class HandleInertiaRequests extends Middleware
{
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
    ) {
        //
    }

    /**
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
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
                'user' => $user,
            ],
            'appearance' => $preferences['appearance']->value,
            'theme' => $preferences['theme']->value,
            'layout' => $preferences['layout'],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'nav' => $user instanceof User ? $this->nav->itemsFor($user) : [],
            'impersonation' => $this->impersonation(),
            'flash' => [
                'success' => $request->hasSession() ? $request->session()->get('success') : null,
                'error' => $request->hasSession() ? $request->session()->get('error') : null,
            ],
        ];
    }

    /**
     * The minimal impersonation state the admin shell banner needs.
     *
     * @return array{impersonator: string}|null
     */
    private function impersonation(): ?array
    {
        if (! $this->impersonate->isImpersonating()) {
            return null;
        }

        $impersonator = $this->impersonate->getImpersonator();

        return $impersonator instanceof User ? ['impersonator' => $impersonator->name] : null;
    }
}
