<?php

declare(strict_types=1);

namespace App\Modules;

use App\Modules\Middleware\EnsureModuleIsEnabled;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
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

        Feature::define('module:'.$this->name(), static fn (): bool => true);

        if (! $this->app->routesAreCached()) {
            Route::middleware([
                'web',
                'auth',
                'verified',
                'role:admin',
                EnsureModuleIsEnabled::class.':'.$this->name(),
            ])
                ->prefix('admin')
                ->name('admin.')
                ->group($this->modulePath('routes/admin.php'));

            $routes = Route::getRoutes();

            if ($routes instanceof RouteCollection) {
                $routes->refreshNameLookups();
            }
        }

        $this->navigation($this->app->make(NavRegistry::class));
        $this->permissions($this->app->make(PermissionRegistry::class));
        $this->widgets($this->app->make(WidgetRegistry::class));
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
     * Resolve a path inside the module, relative to the provider's directory.
     */
    private function modulePath(string $path): string
    {
        return dirname((string) new ReflectionClass($this)->getFileName(), 2).'/'.$path;
    }
}
