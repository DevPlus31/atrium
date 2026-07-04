<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\WidgetRegistry;
use Illuminate\Support\Facades\Gate;
use Laravel\Pennant\Feature;
use Tests\Fixtures\Modules\TestModule\Data\TestModuleWidgetData;
use Tests\Fixtures\Modules\TestModule\Widgets\NotDataWidget;
use Tests\Fixtures\Modules\TestModule\Widgets\TestModuleWidget;

beforeEach(function (): void {
    Feature::define('module:alpha', fn (): bool => true);
    Feature::define('module:bravo', fn (): bool => true);
});

it('resolves permitted widgets sorted by sort order', function (): void {
    $registry = new WidgetRegistry();

    $registry->declare(module: 'alpha', key: 'stats', resolver: fn (): TestModuleWidgetData => new TestModuleWidgetData(count: 7), sort: 2);
    $registry->declare(module: 'alpha', key: 'activity', resolver: TestModuleWidget::class, sort: 1);

    $user = User::factory()->create();

    $widgets = $registry->widgetsFor($user);

    expect($widgets)->toHaveCount(2)
        ->and($widgets[0]['key'])->toBe('activity')
        ->and($widgets[0]['sort'])->toBe(1)
        ->and($widgets[0]['data'])->toBeInstanceOf(TestModuleWidgetData::class)
        ->and($widgets[1]['key'])->toBe('stats')
        ->and($widgets[1]['data']->toArray())->toBe(['count' => 7]);
});

it('filters widgets the user has no permission for', function (): void {
    Gate::define('stats.view', fn (User $user): bool => false);

    $registry = new WidgetRegistry();

    $registry->declare(module: 'alpha', key: 'stats', resolver: TestModuleWidget::class, permission: 'stats.view');
    $registry->declare(module: 'alpha', key: 'activity', resolver: TestModuleWidget::class);

    $user = User::factory()->create();

    $widgets = $registry->widgetsFor($user);

    expect($widgets)->toHaveCount(1)
        ->and($widgets[0]['key'])->toBe('activity');
});

it('filters widgets of modules disabled for the user', function (): void {
    $registry = new WidgetRegistry();

    $registry->declare(module: 'alpha', key: 'stats', resolver: TestModuleWidget::class);
    $registry->declare(module: 'bravo', key: 'activity', resolver: TestModuleWidget::class);

    $user = User::factory()->create();

    Feature::for($user)->deactivate('module:alpha');

    $widgets = $registry->widgetsFor($user);

    expect($widgets)->toHaveCount(1)
        ->and($widgets[0]['key'])->toBe('activity');
});

it('rejects class resolvers that are not invokable', function (): void {
    $registry = new WidgetRegistry();

    $registry->declare(module: 'alpha', key: 'broken', resolver: stdClass::class);

    $user = User::factory()->create();

    $registry->widgetsFor($user);
})->throws(InvalidArgumentException::class, 'Widget resolver [stdClass] must be invokable.');

it('rejects resolvers that do not return a data object', function (): void {
    $registry = new WidgetRegistry();

    $registry->declare(module: 'alpha', key: 'broken', resolver: NotDataWidget::class);

    $user = User::factory()->create();

    $registry->widgetsFor($user);
})->throws(InvalidArgumentException::class, 'must return a data object');
