<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Users\Data\UsersTotalWidgetData;
use Modules\Users\Widgets\UsersTotalWidget;

it('counts every user and builds a fourteen day series', function (): void {
    User::factory()->create(['created_at' => now()]);
    User::factory()->count(2)->create(['created_at' => now()->subDays(3)]);
    User::factory()->create(['created_at' => now()->subDays(13)->startOfDay()]);
    User::factory()->create(['created_at' => now()->subDays(14)]);

    $widget = new UsersTotalWidget();
    $data = $widget();

    expect($data)->toBeInstanceOf(UsersTotalWidgetData::class)
        ->and($data->total)->toBe(5)
        ->and($data->series)->toHaveCount(14)
        ->and($data->series[0])->toBe(['date' => now()->subDays(13)->toDateString(), 'count' => 1])
        ->and($data->series[10])->toBe(['date' => now()->subDays(3)->toDateString(), 'count' => 2])
        ->and($data->series[13])->toBe(['date' => now()->toDateString(), 'count' => 1])
        ->and(collect($data->series)->sum('count'))->toBe(4);
});

it('groups users on their creation day across day boundaries', function (): void {
    $day = now()->subDays(5);

    User::factory()->create(['created_at' => $day->copy()->startOfDay()]);
    User::factory()->create(['created_at' => $day->copy()->endOfDay()]);
    User::factory()->create(['created_at' => $day->copy()->startOfDay()->subSecond()]);

    $widget = new UsersTotalWidget();
    $data = $widget();

    $counts = collect($data->series)->pluck('count', 'date');

    expect($counts->get($day->toDateString()))->toBe(2)
        ->and($counts->get($day->copy()->subDay()->toDateString()))->toBe(1);
});

it('fills days without new users with zero counts', function (): void {
    $widget = new UsersTotalWidget();
    $data = $widget();

    expect($data->total)->toBe(0)
        ->and($data->series)->toHaveCount(14)
        ->and(collect($data->series)->pluck('count')->unique()->all())->toBe([0]);
});
