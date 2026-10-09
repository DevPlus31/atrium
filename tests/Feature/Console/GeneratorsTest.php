<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * The generators write app-modules/ and bootstrap/providers.php, and read lang/en.json. Each
 * test points this worker's application at a throwaway copy of the project
 * root, so scaffolded modules never appear in the real tree — where other
 * parallel workers would try to boot them.
 */
beforeEach(function (): void {
    $this->realBasePath = base_path();
    $this->sandbox = sys_get_temp_dir().'/atrium-generators-'.Str::random(8);

    File::copyDirectory($this->realBasePath.'/stubs', $this->sandbox.'/stubs');
    File::ensureDirectoryExists($this->sandbox.'/bootstrap');
    File::copy($this->realBasePath.'/bootstrap/providers.php', $this->sandbox.'/bootstrap/providers.php');
    File::ensureDirectoryExists($this->sandbox.'/app-modules');
    File::ensureDirectoryExists($this->sandbox.'/lang');
    File::copy($this->realBasePath.'/lang/en.json', $this->sandbox.'/lang/en.json');

    $this->app->setBasePath($this->sandbox);
});

afterEach(function (): void {
    $this->app->setBasePath($this->realBasePath);

    File::deleteDirectory($this->sandbox);
});

it('scaffolds a module and registers its provider', function (): void {
    $this->artisan('make:module', ['name' => 'Zzdemo'])->assertSuccessful();

    expect(File::isDirectory(base_path('app-modules/Zzdemo/Domain/ValueObjects')))->toBeTrue()
        ->and(File::isDirectory(base_path('app-modules/Zzdemo/Infrastructure/Models')))->toBeTrue()
        ->and(File::exists(base_path('app-modules/Zzdemo/Providers/ZzdemoServiceProvider.php')))->toBeTrue()
        ->and(File::exists(base_path('app-modules/Zzdemo/routes/admin.php')))->toBeTrue()
        ->and(File::exists(base_path('app-modules/Zzdemo/tests/Feature/ZzdemoModuleTest.php')))->toBeTrue();

    $provider = File::get(base_path('app-modules/Zzdemo/Providers/ZzdemoServiceProvider.php'));

    expect($provider)->toContain("return 'zzdemo';")
        ->and($provider)->toContain('@ddd-bindings')
        ->and(File::get(base_path('bootstrap/providers.php')))
        ->toContain('Modules\Zzdemo\Providers\ZzdemoServiceProvider::class');
});

it('registers the module provider exactly once', function (): void {
    $this->artisan('make:module', ['name' => 'Zzdemo'])->assertSuccessful();

    $count = mb_substr_count(
        File::get(base_path('bootstrap/providers.php')),
        'ZzdemoServiceProvider::class',
    );

    expect($count)->toBe(1);
});

it('fails when the module already exists', function (): void {
    $this->artisan('make:module', ['name' => 'Zzdemo'])->assertSuccessful();
    $this->artisan('make:module', ['name' => 'Zzdemo'])->assertFailed();
});

it('scaffolds an aggregate and wires the module provider', function (): void {
    $this->artisan('make:module', ['name' => 'Zzdemo'])->assertSuccessful();
    $this->artisan('make:aggregate', ['module' => 'Zzdemo', 'aggregate' => 'Widget'])->assertSuccessful();

    expect(File::exists(base_path('app-modules/Zzdemo/Infrastructure/Models/Widget.php')))->toBeTrue()
        ->and(File::exists(base_path('app-modules/Zzdemo/Domain/Repositories/WidgetRepository.php')))->toBeTrue()
        ->and(File::exists(base_path('app-modules/Zzdemo/Http/Controllers/WidgetController.php')))->toBeTrue()
        ->and(File::exists(base_path('app-modules/Zzdemo/resources/js/components/widgets-columns.tsx')))->toBeTrue()
        ->and(File::exists(base_path('app-modules/Zzdemo/resources/js/components/widgets-form-fields.tsx')))->toBeTrue()
        ->and(File::exists(base_path('app-modules/Zzdemo/tests/Feature/WidgetControllerTest.php')))->toBeTrue()
        ->and(File::exists(base_path('app-modules/Zzdemo/tests/Unit/Actions/WidgetActionsTest.php')))->toBeTrue()
        ->and(File::exists(base_path('app-modules/Zzdemo/tests/Unit/Policies/WidgetPolicyTest.php')))->toBeTrue()
        ->and(File::exists(base_path('app-modules/Zzdemo/tests/Unit/Queries/WidgetsIndexQueryTest.php')))->toBeTrue()
        ->and(File::get(base_path('app-modules/Zzdemo/Actions/UpdateWidget.php')))->toContain('->lockForUpdate($widget)');

    /** @var array<string, string> $module */
    $module = json_decode(File::get(base_path('app-modules/Zzdemo/lang/en.json')), true, flags: JSON_THROW_ON_ERROR);

    expect($module)->toHaveKey('Create widget')
        ->toHaveKey('Widgets')
        ->toHaveKey('Widget created.')
        ->not->toHaveKey('Cancel')
        ->and(File::get(base_path('lang/en.json')))->toBe(File::get($this->realBasePath.'/lang/en.json'));

    // Other locales get the same keys, in English until translated.
    /** @var array<string, string> $french */
    $french = json_decode(File::get(base_path('app-modules/Zzdemo/lang/fr.json')), true, flags: JSON_THROW_ON_ERROR);

    expect($french)->toBe($module);

    $provider = File::get(base_path('app-modules/Zzdemo/Providers/ZzdemoServiceProvider.php'));

    expect($provider)->toContain('$this->app->bind(WidgetRepository::class, EloquentWidgetRepository::class);')
        ->and($provider)->toContain("\$permissions->declare('widgets.view'")
        ->and($provider)->toContain("label: 'Widgets',")
        ->and(File::get(base_path('app-modules/Zzdemo/routes/admin.php')))
        ->toContain("Route::resource('widgets'");
});

it('fails to scaffold an aggregate when the module does not exist', function (): void {
    $this->artisan('make:aggregate', ['module' => 'Nonexistent', 'aggregate' => 'Widget'])->assertFailed();
});

it('fails to scaffold an aggregate that already exists', function (): void {
    $this->artisan('make:module', ['name' => 'Zzdemo'])->assertSuccessful();
    $this->artisan('make:aggregate', ['module' => 'Zzdemo', 'aggregate' => 'Widget'])->assertSuccessful();
    $this->artisan('make:aggregate', ['module' => 'Zzdemo', 'aggregate' => 'Widget'])->assertFailed();
});

it('refuses a second aggregate instead of overwriting the first', function (): void {
    $this->artisan('make:module', ['name' => 'Zzdemo'])->assertSuccessful();
    $this->artisan('make:aggregate', ['module' => 'Zzdemo', 'aggregate' => 'Widget'])->assertSuccessful();

    $routes = File::get(base_path('app-modules/Zzdemo/routes/admin.php'));
    $index = File::get(base_path('app-modules/Zzdemo/resources/js/pages/index.tsx'));

    $this->artisan('make:aggregate', ['module' => 'Zzdemo', 'aggregate' => 'Gadget'])
        ->expectsOutputToContain('Module [Zzdemo] already has an aggregate')
        ->assertFailed();

    expect(File::get(base_path('app-modules/Zzdemo/routes/admin.php')))->toBe($routes)
        ->and(File::get(base_path('app-modules/Zzdemo/resources/js/pages/index.tsx')))->toBe($index)
        ->and(File::exists(base_path('app-modules/Zzdemo/Infrastructure/Models/Gadget.php')))->toBeFalse();
});

it('removes a module, its provider registration, and names the tables it leaves', function (): void {
    $this->artisan('make:module', ['name' => 'Zzdemo'])->assertSuccessful();
    $this->artisan('make:aggregate', ['module' => 'Zzdemo', 'aggregate' => 'Widget'])->assertSuccessful();

    $this->artisan('module:remove', ['name' => 'Zzdemo', '--force' => true])
        ->expectsOutputToContain('Module [Zzdemo] removed.')
        ->expectsOutputToContain('widgets')
        ->assertSuccessful();

    expect(File::isDirectory(base_path('app-modules/Zzdemo')))->toBeFalse()
        ->and(File::get(base_path('bootstrap/providers.php')))->not->toContain('ZzdemoServiceProvider')
        ->and(File::get(base_path('bootstrap/providers.php')))->toBe(File::get($this->realBasePath.'/bootstrap/providers.php'));
});

it('says when a removed module created no tables', function (): void {
    $this->artisan('make:module', ['name' => 'Zzdemo'])->assertSuccessful();

    $this->artisan('module:remove', ['name' => 'Zzdemo', '--force' => true])
        ->expectsOutputToContain('The module created no tables')
        ->assertSuccessful();
});

it('keeps the module when the removal is not confirmed', function (): void {
    $this->artisan('make:module', ['name' => 'Zzdemo'])->assertSuccessful();

    $this->artisan('module:remove', ['name' => 'Zzdemo'])
        ->expectsConfirmation('Delete app-modules/Zzdemo and unregister its provider?', 'no')
        ->expectsOutputToContain('Nothing removed.')
        ->assertSuccessful();

    expect(File::isDirectory(base_path('app-modules/Zzdemo')))->toBeTrue();
});

it('warns about code elsewhere that still mentions the removed module', function (): void {
    $this->artisan('make:module', ['name' => 'Zzdemo'])->assertSuccessful();
    $this->artisan('make:module', ['name' => 'Zzother'])->assertSuccessful();
    File::put(base_path('app-modules/Zzother/Listens.php'), "<?php\n\n// 'Modules\\Zzdemo\\Domain\\Events\\Happened'\n");

    $this->artisan('module:remove', ['name' => 'Zzdemo', '--force' => true])
        ->expectsOutputToContain('app-modules/Zzother/Listens.php')
        ->assertSuccessful();
});

it('fails to remove a module that does not exist', function (): void {
    $this->artisan('module:remove', ['name' => 'Missing', '--force' => true])->assertFailed();
});

it('refuses names that are not plain identifiers', function (string $command, array $arguments): void {
    $this->artisan($command, $arguments)
        ->expectsOutputToContain('is not a valid name')
        ->assertFailed();

    expect(File::directories(base_path('app-modules')))->toBe([]);
})->with([
    'module with a path' => ['make:module', ['name' => '../Escape']],
    'module starting with a digit' => ['make:module', ['name' => '9Lives']],
    'aggregate with a path' => ['make:aggregate', ['module' => 'Zzdemo', 'aggregate' => '../Widget']],
    'removal with a path' => ['module:remove', ['name' => '../app', '--force' => true]],
]);

it('says when the provider line was edited by hand and could not be removed', function (): void {
    $this->artisan('make:module', ['name' => 'Zzdemo'])->assertSuccessful();
    File::put(base_path('bootstrap/providers.php'), str_replace(
        'Modules\Zzdemo\Providers\ZzdemoServiceProvider::class',
        'ZzdemoProvider::class',
        File::get(base_path('bootstrap/providers.php')),
    ));

    $this->artisan('module:remove', ['name' => 'Zzdemo', '--force' => true])
        ->expectsOutputToContain('check it by hand')
        ->assertSuccessful();
});
