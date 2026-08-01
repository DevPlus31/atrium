<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

/**
 * Both generators live in one test file so they run serially in a single
 * worker under `--parallel`; only `make:module` mutates bootstrap/providers.php,
 * and afterEach restores it BEFORE deleting the module so the registered
 * provider class always resolves for any concurrent worker.
 */
beforeEach(function (): void {
    $this->providersBackup = File::get(base_path('bootstrap/providers.php'));
});

afterEach(function (): void {
    File::put(base_path('bootstrap/providers.php'), $this->providersBackup);
    File::deleteDirectory(base_path('app-modules/Zzdemo'));
    File::deleteDirectory(base_path('tests/Feature/Modules/Zzdemo'));
});

it('scaffolds a module and registers its provider', function (): void {
    $this->artisan('make:module', ['name' => 'Zzdemo'])->assertSuccessful();

    expect(File::isDirectory(base_path('app-modules/Zzdemo/Domain/ValueObjects')))->toBeTrue()
        ->and(File::isDirectory(base_path('app-modules/Zzdemo/Infrastructure/Models')))->toBeTrue()
        ->and(File::exists(base_path('app-modules/Zzdemo/Providers/ZzdemoServiceProvider.php')))->toBeTrue()
        ->and(File::exists(base_path('app-modules/Zzdemo/routes/admin.php')))->toBeTrue()
        ->and(File::exists(base_path('tests/Feature/Modules/Zzdemo/ZzdemoModuleTest.php')))->toBeTrue();

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
        ->and(File::exists(base_path('tests/Feature/Modules/Zzdemo/WidgetControllerTest.php')))->toBeTrue();

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
