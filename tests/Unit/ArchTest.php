<?php

declare(strict_types=1);

use App\Domain\Exceptions\DomainException;
use App\Modules\IndexQuery;
use App\Modules\ModuleServiceProvider;
use App\Modules\NavRegistry;
use App\Modules\PermissionRegistry;
use App\Modules\WidgetRegistry;
use App\Notifications\AppNotification;
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
    // Abstract bases: cannot be final because modules extend them.
    AppNotification::class,
    DomainException::class,
    IndexQuery::class,
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

// Every module with an Actions directory, discovered on disk so new modules
// are covered without editing this file.
$moduleActionNamespaces = array_map(
    static fn (string $path): string => 'Modules\\'.basename(dirname($path)).'\\Actions',
    glob(dirname(__DIR__, 2).'/app-modules/*/Actions', GLOB_ONLYDIR) ?: [],
);

arch('module actions are final and readonly')
    ->expect($moduleActionNamespaces)
    ->toBeFinal()
    ->toBeReadonly();

arch('the shell never depends on a module')
    ->expect('App')
    ->not->toUse('Modules');

it('keeps module routes out of the shell route files', function (): void {
    expect(file_get_contents(base_path('routes/web.php')))->not->toContain('Modules\\');
});

$moduleWidgetNamespaces = array_map(
    static fn (string $path): string => 'Modules\\'.basename(dirname($path)).'\\Widgets',
    glob(dirname(__DIR__, 2).'/app-modules/*/Widgets', GLOB_ONLYDIR) ?: [],
);

arch('module widget resolvers are final and readonly')
    ->expect($moduleWidgetNamespaces)
    ->toBeFinal()
    ->toBeReadonly();

//
