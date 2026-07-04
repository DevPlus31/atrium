<?php

declare(strict_types=1);

namespace Modules\System\Providers;

use App\Models\User;
use App\Modules\ModuleServiceProvider;
use App\Modules\NavRegistry;
use App\Modules\PermissionRegistry;
use Illuminate\Support\Facades\Gate;

final class SystemServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        // Deferred until after boot: Pulse re-defines `viewPulse` when the Gate
        // singleton resolves (callAfterResolving), so defining here directly
        // would be overwritten by its local-environment-only default.
        $this->app->booted(static function (): void {
            Gate::define('viewPulse', static fn (User $user): bool => $user->can('system.pulse.view'));
            Gate::define('viewLogViewer', static fn (User $user): bool => $user->can('system.logs.view'));
            Gate::define('downloadLogFile', static fn (User $user): bool => $user->can('system.logs.view'));
            Gate::define('downloadLogFolder', static fn (User $user): bool => $user->can('system.logs.view'));
            Gate::define('deleteLogFile', static fn (User $user): bool => false);
            Gate::define('deleteLogFolder', static fn (User $user): bool => false);
        });
    }

    protected function name(): string
    {
        return 'system';
    }

    protected function navigation(NavRegistry $nav): void
    {
        $nav->add(
            module: $this->name(),
            label: 'Pulse',
            routeName: 'pulse',
            icon: 'activity',
            permission: 'system.pulse.view',
            group: 'System',
            sort: 90,
            external: true,
        );

        $nav->add(
            module: $this->name(),
            label: 'Horizon',
            routeName: 'horizon.index',
            icon: 'gauge',
            permission: 'system.horizon.view',
            group: 'System',
            sort: 91,
            external: true,
        );

        $nav->add(
            module: $this->name(),
            label: 'Logs',
            routeName: 'log-viewer.index',
            icon: 'file-text',
            permission: 'system.logs.view',
            group: 'System',
            sort: 92,
            external: true,
        );
    }

    protected function permissions(PermissionRegistry $permissions): void
    {
        $permissions->declare('system.pulse.view', roles: ['admin']);
        $permissions->declare('system.horizon.view', roles: ['admin']);
        $permissions->declare('system.logs.view', roles: ['admin']);
    }
}
