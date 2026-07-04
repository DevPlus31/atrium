<?php

declare(strict_types=1);

use App\Modules\ModuleServiceProvider;
use App\Modules\NavRegistry;
use App\Modules\PermissionRegistry;
use App\Modules\WidgetRegistry;
use App\Providers\TypeScriptTransformerServiceProvider;

arch()->preset()->php();
arch()->preset()->strict()->ignoring([
    ModuleServiceProvider::class,
    TypeScriptTransformerServiceProvider::class,
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
    ])
    ->toExtend(ModuleServiceProvider::class);

//
