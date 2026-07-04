<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Data\NavItemData;
use App\Modules\NavRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Laravel\Pennant\Feature;

beforeEach(function (): void {
    Route::get('admin/alpha', fn (): string => 'alpha')->name('admin.alpha.index');
    Route::get('admin/bravo', fn (): string => 'bravo')->name('admin.bravo.index');
    Route::get('admin/charlie', fn (): string => 'charlie')->name('admin.charlie.index');
    Route::getRoutes()->refreshNameLookups();

    Feature::define('module:alpha', fn (): bool => true);
    Feature::define('module:bravo', fn (): bool => true);
});

it('returns visible items sorted by group and sort order', function (): void {
    $registry = new NavRegistry();

    $registry->add(module: 'alpha', label: 'Bravo', routeName: 'admin.bravo.index', group: 'Content', sort: 2);
    $registry->add(module: 'alpha', label: 'Alpha', routeName: 'admin.alpha.index', icon: 'users', group: 'Content', sort: 1);
    $registry->add(module: 'bravo', label: 'Charlie', routeName: 'admin.charlie.index', sort: 5);

    $user = User::factory()->create();

    $items = $registry->itemsFor($user);

    expect($items)->toHaveCount(3)
        ->and($items[0]->label)->toBe('Charlie')
        ->and($items[1]->label)->toBe('Alpha')
        ->and($items[2]->label)->toBe('Bravo');
});

it('serializes items into the shared nav shape', function (): void {
    $registry = new NavRegistry();

    $registry->add(module: 'alpha', label: 'Alpha', routeName: 'admin.alpha.index', icon: 'users', group: 'Content', sort: 1);

    $user = User::factory()->create();

    $items = $registry->itemsFor($user);

    expect($items[0])->toBeInstanceOf(NavItemData::class)
        ->and($items[0]->toArray())->toBe([
            'label' => 'Alpha',
            'routeName' => 'admin.alpha.index',
            'href' => route('admin.alpha.index'),
            'icon' => 'users',
            'group' => 'Content',
            'sort' => 1,
        ]);
});

it('filters items the user has no permission for', function (): void {
    Gate::define('alpha.view', fn (User $user): bool => false);
    Gate::define('bravo.view', fn (User $user): bool => true);

    $registry = new NavRegistry();

    $registry->add(module: 'alpha', label: 'Alpha', routeName: 'admin.alpha.index', permission: 'alpha.view');
    $registry->add(module: 'alpha', label: 'Bravo', routeName: 'admin.bravo.index', permission: 'bravo.view');

    $user = User::factory()->create();

    $items = $registry->itemsFor($user);

    expect($items)->toHaveCount(1)
        ->and($items[0]->label)->toBe('Bravo');
});

it('filters items of modules disabled for the user', function (): void {
    $registry = new NavRegistry();

    $registry->add(module: 'alpha', label: 'Alpha', routeName: 'admin.alpha.index');
    $registry->add(module: 'bravo', label: 'Charlie', routeName: 'admin.charlie.index');

    $user = User::factory()->create();

    Feature::for($user)->deactivate('module:alpha');

    $items = $registry->itemsFor($user);

    expect($items)->toHaveCount(1)
        ->and($items[0]->label)->toBe('Charlie');
});
