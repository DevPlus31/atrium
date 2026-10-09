<?php

declare(strict_types=1);

use App\Enums\Area;
use App\Models\User;
use App\Modules\WidgetRegistry;
use Inertia\DeferProp;
use Laravel\Pennant\Feature;
use Modules\Dashboard\Data\WidgetDescriptorData;
use Modules\Dashboard\Queries\DashboardWidgetsQuery;
use Tests\Fixtures\Modules\TestModule\Data\TestModuleWidgetData;

beforeEach(function (): void {
    Feature::define('module:alpha', fn (): bool => true);
});

it("describes the area's widgets and defers each one's data", function (): void {
    $registry = new WidgetRegistry();
    $registry->declare(module: 'alpha', key: 'alpha.count', resolver: fn (): TestModuleWidgetData => new TestModuleWidgetData(count: 3), sort: 2);
    $registry->declare(module: 'alpha', key: 'alpha.first', resolver: fn (): TestModuleWidgetData => new TestModuleWidgetData(count: 1), sort: 1);
    $registry->declare(module: 'alpha', key: 'alpha.member', resolver: fn (): TestModuleWidgetData => new TestModuleWidgetData(count: 9), area: Area::Member);

    $user = User::factory()->create();

    $props = new DashboardWidgetsQuery($registry)->for($user, Area::Admin);

    expect($props['widgets'])->toEqual([
        new WidgetDescriptorData(key: 'alpha.first', prop: 'widget:alpha_first', sort: 1),
        new WidgetDescriptorData(key: 'alpha.count', prop: 'widget:alpha_count', sort: 2),
    ])
        ->and($props)->toHaveKeys(['widget:alpha_first', 'widget:alpha_count'])
        ->not->toHaveKey('widget:alpha_member')
        ->and($props['widget:alpha_count'])->toBeInstanceOf(DeferProp::class);

    $deferred = $props['widget:alpha_count'];
    assert($deferred instanceof DeferProp);

    expect($deferred->group())->toBe('widgets')
        ->and($deferred()?->toArray())->toBe(['count' => 3]);
});

it('gives an area without widgets an empty list', function (): void {
    $props = new DashboardWidgetsQuery(new WidgetRegistry())->for(User::factory()->create(), Area::Member);

    expect($props)->toBe(['widgets' => []]);
});
