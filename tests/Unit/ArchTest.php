<?php

declare(strict_types=1);

use App\Domain\Exceptions\DomainException;
use App\Modules\ModuleServiceProvider;
use App\Modules\NavRegistry;
use App\Modules\PermissionRegistry;
use App\Modules\WidgetRegistry;
use App\Providers\HorizonServiceProvider;
use App\Providers\TypeScriptTransformerServiceProvider;
use Illuminate\Support\Str;

// Module providers, parsed from the application's registered providers so that
// adding a module (via `make:module`, which appends to bootstrap/providers.php)
// needs no edit here. Both the strict-preset ignore list and the `toExtend`
// contract check below derive from it.
preg_match_all(
    '/(Modules\\\\[A-Za-z0-9_\\\\]+ServiceProvider)::class/',
    (string) file_get_contents(dirname(__DIR__, 2).'/bootstrap/providers.php'),
    $matches,
);

/** @var list<class-string> $moduleProviders */
$moduleProviders = $matches[1];

arch()->preset()->php();
arch()->preset()->strict()->ignoring([
    // Abstract base: cannot be final because modules extend it.
    DomainException::class,
    HorizonServiceProvider::class,
    ModuleServiceProvider::class,
    TypeScriptTransformerServiceProvider::class,
    // Concrete providers are final but override protected hook methods, which
    // the strict preset flags; ignore them as the module contract intends.
    ...$moduleProviders,
]);
arch()->preset()->laravel()->ignoring([
    ModuleServiceProvider::class,
    // Domain exceptions extend the framework RuntimeException; the Laravel
    // preset otherwise forbids Throwables outside App\Exceptions. Module
    // exceptions live under Modules\* and are unaffected by this App\ rule.
    'App\Domain\Exceptions',
]);
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
        ...array_map(static fn (string $provider): string => Str::beforeLast($provider, '\\'), $moduleProviders),
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
