<?php

declare(strict_types=1);

use App\Modules\ModuleServiceProvider;
use App\Modules\NavRegistry;
use App\Modules\PermissionRegistry;
use App\Modules\WidgetRegistry;
use App\Providers\HorizonServiceProvider;
use App\Providers\TypeScriptTransformerServiceProvider;
use Modules\Audit\Providers\AuditServiceProvider;
use Modules\Dashboard\Providers\DashboardServiceProvider;
use Modules\Roles\Providers\RolesServiceProvider;
use Modules\System\Providers\SystemServiceProvider;
use Modules\Users\Providers\UsersServiceProvider;

arch()->preset()->php();
arch()->preset()->strict()->ignoring([
    HorizonServiceProvider::class,
    ModuleServiceProvider::class,
    TypeScriptTransformerServiceProvider::class,
    AuditServiceProvider::class,
    DashboardServiceProvider::class,
    RolesServiceProvider::class,
    SystemServiceProvider::class,
    UsersServiceProvider::class,
]);
arch()->preset()->laravel()->ignoring(ModuleServiceProvider::class);
arch()->preset()->security()->ignoring([
    'assert',
]);

arch('controllers')
    ->expect('App\Http\Controllers')
    ->not->toBeUsed();

arch('actions are final')
    ->expect('App\Actions')
    ->toBeFinal();

arch('module contract registries are final')
    ->expect([
        NavRegistry::class,
        PermissionRegistry::class,
        WidgetRegistry::class,
    ])
    ->toBeFinal();

arch('module service providers extend the module contract')
    ->expect([
        'Tests\Fixtures\Modules\TestModule\Providers',
        'Tests\Fixtures\Modules\BareModule\Providers',
        'Modules\Audit\Providers',
        'Modules\Dashboard\Providers',
        'Modules\Roles\Providers',
        'Modules\System\Providers',
        'Modules\Users\Providers',
    ])
    ->toExtend(ModuleServiceProvider::class);

arch('module actions are final and readonly')
    ->expect('Modules\Users\Actions')
    ->toBeFinal()
    ->toBeReadonly();

arch('roles module actions are final and readonly')
    ->expect('Modules\Roles\Actions')
    ->toBeFinal()
    ->toBeReadonly();

arch('module widget resolvers are final and readonly')
    ->expect('Modules\Users\Widgets')
    ->toBeFinal()
    ->toBeReadonly();

//
