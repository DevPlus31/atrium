<?php

declare(strict_types=1);

use App\Enums\Area;
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

it('resolves closure resolvers', function (): void {
    $registry = new WidgetRegistry();

    $registry->declare(module: 'alpha', key: 'stats', resolver: fn (): TestModuleWidgetData => new TestModuleWidgetData(count: 7));

    $user = User::factory()->create();

    expect($registry->resolveFor($user, 'stats')?->toArray())->toBe(['count' => 7]);
});

it('filters widgets the user has no permission for', function (): void {
    Gate::define('stats.view', fn (User $user): bool => false);

    $registry = new WidgetRegistry();

    $registry->declare(module: 'alpha', key: 'stats', resolver: TestModuleWidget::class, permission: 'stats.view');
    $registry->declare(module: 'alpha', key: 'activity', resolver: TestModuleWidget::class);

    $user = User::factory()->create();

    expect($registry->descriptorsFor($user))->toBe([['key' => 'activity', 'sort' => 0]]);
});

it('filters widgets of modules disabled for the user', function (): void {
    $registry = new WidgetRegistry();

    $registry->declare(module: 'alpha', key: 'stats', resolver: TestModuleWidget::class);
    $registry->declare(module: 'bravo', key: 'activity', resolver: TestModuleWidget::class);

    $user = User::factory()->create();

    Feature::for($user)->deactivate('module:alpha');

    expect($registry->descriptorsFor($user))->toBe([['key' => 'activity', 'sort' => 0]]);
});

it('lists permitted widget descriptors sorted by sort order without resolving them', function (): void {
    $registry = new WidgetRegistry();

    $registry->declare(module: 'alpha', key: 'stats', resolver: static function (): TestModuleWidgetData {
        throw new RuntimeException('Descriptors must not resolve widget data.');
    }, sort: 2);
    $registry->declare(module: 'alpha', key: 'activity', resolver: TestModuleWidget::class, sort: 1);

    $user = User::factory()->create();

    expect($registry->descriptorsFor($user))->toBe([
        ['key' => 'activity', 'sort' => 1],
        ['key' => 'stats', 'sort' => 2],
    ]);
});

it('omits descriptors of widgets the user has no permission for', function (): void {
    Gate::define('stats.view', fn (User $user): bool => false);

    $registry = new WidgetRegistry();

    $registry->declare(module: 'alpha', key: 'stats', resolver: TestModuleWidget::class, permission: 'stats.view');
    $registry->declare(module: 'bravo', key: 'activity', resolver: TestModuleWidget::class);

    $user = User::factory()->create();

    Feature::for($user)->deactivate('module:bravo');

    expect($registry->descriptorsFor($user))->toBe([]);
});

it('resolves a permitted widget by key', function (): void {
    $registry = new WidgetRegistry();

    $registry->declare(module: 'alpha', key: 'stats', resolver: TestModuleWidget::class);

    $user = User::factory()->create();

    $data = $registry->resolveFor($user, 'stats');

    expect($data)->toBeInstanceOf(TestModuleWidgetData::class)
        ->and($data->toArray())->toBe(['count' => 3]);
});

it('resolves unknown or forbidden widget keys to null', function (): void {
    Gate::define('stats.view', fn (User $user): bool => false);

    $registry = new WidgetRegistry();

    $registry->declare(module: 'alpha', key: 'stats', resolver: TestModuleWidget::class, permission: 'stats.view');

    $user = User::factory()->create();

    expect($registry->resolveFor($user, 'stats'))->toBeNull()
        ->and($registry->resolveFor($user, 'unknown'))->toBeNull();
});

it('rejects class resolvers that are not invokable', function (): void {
    $registry = new WidgetRegistry();

    $registry->declare(module: 'alpha', key: 'broken', resolver: stdClass::class);

    $user = User::factory()->create();

    $registry->resolveFor($user, 'broken');
})->throws(InvalidArgumentException::class, 'Widget resolver [stdClass] must be invokable.');

it('rejects resolvers that do not return a data object', function (): void {
    $registry = new WidgetRegistry();

    $registry->declare(module: 'alpha', key: 'broken', resolver: NotDataWidget::class);

    $user = User::factory()->create();

    $registry->resolveFor($user, 'broken');
})->throws(InvalidArgumentException::class, 'must return a data object');

it('keeps the admin and member areas apart', function (): void {
    $registry = new WidgetRegistry();

    $registry->declare(module: 'alpha', key: 'admin-only', resolver: TestModuleWidget::class);
    $registry->declare(module: 'alpha', key: 'member-only', resolver: TestModuleWidget::class, area: Area::Member);

    $user = User::factory()->create();

    expect($registry->descriptorsFor($user))->toBe([['key' => 'admin-only', 'sort' => 0]])
        ->and($registry->descriptorsFor($user, Area::Member))->toBe([['key' => 'member-only', 'sort' => 0]])
        ->and($registry->resolveFor($user, 'member-only'))->toBeNull()
        ->and($registry->resolveFor($user, 'member-only', Area::Member))->toBeInstanceOf(TestModuleWidgetData::class);
});

it('passes the viewing user to the resolver', function (): void {
    $registry = new WidgetRegistry();

    $registry->declare(module: 'alpha', key: 'mine', resolver: fn (User $viewer): TestModuleWidgetData => new TestModuleWidgetData(count: mb_strlen($viewer->name)));

    $user = User::factory()->create(['name' => 'Jane']);

    expect($registry->resolveFor($user, 'mine')?->toArray())->toBe(['count' => 4]);
});

it('hides a widget whose resolver has nothing for the user', function (): void {
    $registry = new WidgetRegistry();

    $registry->declare(module: 'alpha', key: 'empty', resolver: fn (): ?TestModuleWidgetData => null);

    $user = User::factory()->create();

    expect($registry->resolveFor($user, 'empty'))->toBeNull();
});
