<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Modules\ListenerClassResolver;
use App\Modules\NavRegistry;
use App\Modules\PermissionRegistry;
use App\Modules\SearchRegistry;
use App\Modules\WidgetRegistry;
use App\View\Composers\MailBrandingComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Events\DiscoverEvents;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Inertia\ExceptionResponse;
use Inertia\Inertia;
use Lab404\Impersonate\Services\ImpersonateManager;
use Spatie\Activitylog\Models\Activity;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * The statuses rendered as the branded error page.
     *
     * @var list<int>
     */
    private const array ERROR_PAGES = [403, 404, 429, 500, 503];

    private const int API_REQUESTS_PER_MINUTE = 60;

    public function register(): void
    {
        $this->app->singleton(NavRegistry::class);
        $this->app->singleton(PermissionRegistry::class);
        $this->app->singleton(WidgetRegistry::class);
        $this->app->singleton(SearchRegistry::class);

        DiscoverEvents::guessClassNamesUsing(ListenerClassResolver::classFromFile(...));
    }

    public function boot(): void
    {
        $permissions = $this->app->make(PermissionRegistry::class);

        // Declared permissions are answered here, in one place (Spatie's own
        // gate hook is off, see config/permission.php):
        // - an API token carries only the permissions ticked when it was
        //   made; one it lacks is denied, whatever the user's roles;
        // - a super-admin holds every declared permission;
        // - anyone else needs it through a role.
        // Policies and other gates still run on top: they compose
        // permissions with rules (system roles, finished orders,
        // self-deletion) that bind everyone, super-admins included.
        Gate::before(static fn (User $user, string $ability): ?bool => $permissions->has($ability) ? self::checkPermission($user, $ability) : null);

        Gate::define(User::PANEL_ABILITY, static fn (User $user): bool => $user->canAccessPanel());

        Activity::creating(static function (Activity $activity): void {
            self::recordImpersonator($activity, resolve(ImpersonateManager::class));
        });

        Inertia::handleExceptionsUsing($this->renderErrorPage(...));

        View::composer('mail::message', MailBrandingComposer::class);

        RateLimiter::for('api', static fn (Request $request): Limit => Limit::perMinute(self::API_REQUESTS_PER_MINUTE)
            ->by($request->user()?->getAuthIdentifier() ?? $request->ip()));
    }

    /**
     * True grants, false denies (even if a policy would allow), null lets the
     * Gate carry on (and deny, since no gate defines a permission name).
     */
    private static function checkPermission(User $user, string $ability): ?bool
    {
        if (! $user->tokenAllows($ability)) {
            return false;
        }

        return $user->isSuperAdmin() || $user->checkPermissionTo($ability) ? true : null;
    }

    /**
     * While an admin impersonates someone, the session's user is the
     * impersonated account, so every audit entry also names the admin who
     * really acted.
     */
    private static function recordImpersonator(Activity $activity, ImpersonateManager $impersonation): void
    {
        if (! $impersonation->isImpersonating()) {
            return;
        }

        $id = $impersonation->getImpersonatorId();

        $activity->properties = collect($activity->properties)->put('impersonator', [
            'id' => $id,
            'name' => User::query()->whereKey($id)->value('name'),
        ]);
    }

    /**
     * Errors render as the app's own branded page (resources/js/pages/
     * error.tsx) instead of Laravel's default HTML, keeping the theme and
     * shared props. An expired page (419) goes back with a toast instead.
     * JSON clients, other statuses and — while APP_DEBUG is on — server
     * errors fall through to Laravel's default rendering.
     */
    private function renderErrorPage(ExceptionResponse $error): ExceptionResponse|RedirectResponse|null
    {
        $status = $error->statusCode();

        if ($error->request->expectsJson() && ! $error->request->hasHeader('X-Inertia')) {
            return null;
        }

        if ($status === 419) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('The page expired. Please try again.')]);

            return back();
        }

        if (! in_array($status, self::ERROR_PAGES, true) || ($status === 500 && config('app.debug') === true)) {
            return null;
        }

        return $error->render('error', ['status' => $status])->withSharedData();
    }
}
