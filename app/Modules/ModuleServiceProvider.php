<?php

declare(strict_types=1);

namespace App\Modules;

use App\Models\User;
use App\Modules\Middleware\EnsureModuleIsEnabled;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\View\FileViewFinder;
use Laravel\Pennant\Feature;
use ReflectionClass;

abstract class ModuleServiceProvider extends ServiceProvider
{
    /**
     * The kebab-case name of the module.
     */
    abstract protected function name(): string;

    final public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('Database/Migrations'));
        $this->loadJsonTranslationsFrom($this->modulePath('lang'));

        Feature::define('module:'.$this->name(), static fn (): bool => true);

        $this->registerPages();

        if (! $this->app->routesAreCached()) {
            Route::middleware([
                'web',
                'auth',
                'verified',
                'can:'.User::PANEL_ABILITY,
                EnsureModuleIsEnabled::class.':'.$this->name(),
            ])
                ->prefix('admin')
                ->name('admin.')
                ->group($this->modulePath('routes/admin.php'));

            // Optional non-admin routes (e.g. actions available to users
            // without panel access); the module applies its own middleware.
            if (is_file($this->modulePath('routes/web.php'))) {
                Route::middleware('web')->group($this->modulePath('routes/web.php'));
            }

            // Optional API endpoints: version 1, token-authenticated. Gates
            // see only the permissions the token was given.
            if (is_file($this->modulePath('routes/api.php'))) {
                Route::middleware(['api', 'auth:sanctum', EnsureModuleIsEnabled::class.':'.$this->name()])
                    ->prefix('api/v1')
                    ->name('api.v1.')
                    ->group($this->modulePath('routes/api.php'));
            }

            $routes = Route::getRoutes();

            if ($routes instanceof RouteCollection) {
                $routes->refreshNameLookups();
            }
        }

        $this->navigation($this->app->make(NavRegistry::class));
        $this->permissions($this->app->make(PermissionRegistry::class));
        $this->widgets($this->app->make(WidgetRegistry::class));
        $this->search($this->app->make(SearchRegistry::class));
    }

    /**
     * Declare the module's navigation items.
     */
    protected function navigation(NavRegistry $nav): void
    {
        //
    }

    /**
     * Declare the module's permissions and default role assignments.
     */
    protected function permissions(PermissionRegistry $permissions): void
    {
        //
    }

    /**
     * Declare the module's dashboard widgets.
     */
    protected function widgets(WidgetRegistry $widgets): void
    {
        //
    }

    /**
     * Declare the module's global search: what the command palette finds.
     */
    protected function search(SearchRegistry $search): void
    {
        //
    }

    /**
     * Teach Inertia's page finder the module's `<module>::<page>` components,
     * so `ensure_pages_exist` and the testing `component()` assertion find
     * pages under `resources/js/pages`. The prefix is the lowercased module
     * folder (`UserPrefs` → `userprefs::index`), exactly as the frontend
     * resolver (resources/js/lib/resolve-page.ts) addresses pages.
     */
    private function registerPages(): void
    {
        $name = Str::lower(basename($this->modulePath('')));
        $pages = $this->modulePath('resources/js/pages');

        $this->app->extend('inertia.view-finder', static function (FileViewFinder $finder) use ($name, $pages): FileViewFinder {
            $finder->addNamespace($name, $pages);

            return $finder;
        });
    }

    /**
     * Resolve a path inside the module, relative to the provider's directory.
     */
    private function modulePath(string $path): string
    {
        return dirname((string) new ReflectionClass($this)->getFileName(), 2).'/'.$path;
    }
}
