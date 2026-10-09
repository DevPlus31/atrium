<?php

declare(strict_types=1);

namespace Modules\Users\Providers;

use App\Models\User;
use App\Modules\ModuleServiceProvider;
use App\Modules\NavRegistry;
use App\Modules\PermissionRegistry;
use App\Modules\SearchRegistry;
use App\Modules\WidgetRegistry;
use Illuminate\Support\Facades\Gate;
use Modules\Users\Console\Commands\CreateAdminUserCommand;
use Modules\Users\Domain\Repositories\InvitationRepository;
use Modules\Users\Domain\Repositories\UserRepository;
use Modules\Users\Infrastructure\Repositories\EloquentInvitationRepository;
use Modules\Users\Infrastructure\Repositories\EloquentUserRepository;
use Modules\Users\Policies\UserPolicy;
use Modules\Users\Search\UsersSearch;
use Modules\Users\Widgets\RecentUsersWidget;
use Modules\Users\Widgets\UsersTotalWidget;

final class UsersServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        Gate::policy(User::class, UserPolicy::class);

        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
        $this->app->bind(InvitationRepository::class, EloquentInvitationRepository::class);

        $this->commands([CreateAdminUserCommand::class]);
    }

    protected function name(): string
    {
        return 'users';
    }

    protected function navigation(NavRegistry $nav): void
    {
        $nav->add(
            module: $this->name(),
            label: 'Users',
            routeName: 'admin.users.index',
            icon: 'users',
            permission: 'users.view',
            group: 'Management',
            sort: 10,
        );
    }

    protected function permissions(PermissionRegistry $permissions): void
    {
        $permissions->declare('users.view', roles: [User::PANEL_ROLE]);
        $permissions->declare('users.create', roles: [User::PANEL_ROLE]);
        $permissions->declare('users.update', roles: [User::PANEL_ROLE]);
        $permissions->declare('users.delete', roles: [User::PANEL_ROLE]);
        $permissions->declare('users.export', roles: [User::PANEL_ROLE]);
        $permissions->declare('users.impersonate', roles: [User::PANEL_ROLE]);
    }

    protected function widgets(WidgetRegistry $widgets): void
    {
        $widgets->declare(
            module: $this->name(),
            key: 'users.total',
            resolver: UsersTotalWidget::class,
            permission: 'users.view',
            sort: 0,
        );

        $widgets->declare(
            module: $this->name(),
            key: 'users.recent',
            resolver: RecentUsersWidget::class,
            permission: 'users.view',
            sort: 10,
        );
    }

    protected function search(SearchRegistry $search): void
    {
        $search->add(
            module: $this->name(),
            label: 'Users',
            searcher: UsersSearch::class,
            icon: 'users',
            permission: 'users.view',
            sort: 10,
        );
    }
}
