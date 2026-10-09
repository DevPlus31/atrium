<?php

declare(strict_types=1);

namespace Modules\Settings\Providers;

use App\Modules\ModuleServiceProvider;
use App\Modules\NavRegistry;
use App\Modules\PermissionRegistry;

/**
 * The admin "Settings" area: app-wide settings (App\Settings\GeneralSettings,
 * read by the shell). Other modules add their own settings pages to the same
 * menu group ("Settings") — see the Shop module.
 */
final class SettingsServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'settings';
    }

    protected function navigation(NavRegistry $nav): void
    {
        $nav->add(
            module: $this->name(),
            label: 'General',
            routeName: 'admin.settings.general.edit',
            icon: 'settings',
            permission: 'settings.update',
            group: 'Settings',
            sort: 0,
        );

        $nav->add(
            module: $this->name(),
            label: 'Announcement',
            routeName: 'admin.settings.announcement.edit',
            icon: 'megaphone',
            permission: 'settings.update',
            group: 'Settings',
            sort: 5,
        );
    }

    protected function permissions(PermissionRegistry $permissions): void
    {
        $permissions->declare('settings.update', roles: ['admin']);
    }
}
