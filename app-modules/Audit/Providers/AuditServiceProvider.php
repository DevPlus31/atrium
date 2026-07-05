<?php

declare(strict_types=1);

namespace Modules\Audit\Providers;

use App\Modules\ModuleServiceProvider;
use App\Modules\NavRegistry;
use App\Modules\PermissionRegistry;
use Illuminate\Support\Facades\Gate;
use Modules\Audit\Policies\ActivityPolicy;
use Spatie\Activitylog\Models\Activity;

final class AuditServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        Gate::policy(Activity::class, ActivityPolicy::class);
    }

    protected function name(): string
    {
        return 'audit';
    }

    protected function navigation(NavRegistry $nav): void
    {
        $nav->add(
            module: $this->name(),
            label: 'Audit log',
            routeName: 'admin.audit.index',
            icon: 'history',
            permission: 'audit.view',
            group: 'System',
            sort: 80,
        );
    }

    protected function permissions(PermissionRegistry $permissions): void
    {
        $permissions->declare('audit.view', roles: ['admin']);
    }
}
