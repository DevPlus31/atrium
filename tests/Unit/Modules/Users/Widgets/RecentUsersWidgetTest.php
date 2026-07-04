<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Users\Data\RecentUsersWidgetData;
use Modules\Users\Widgets\RecentUsersWidget;

it('lists the five most recent users newest first', function (): void {
    foreach (range(1, 6) as $days) {
        User::factory()->create([
            'name' => sprintf('User %d', $days),
            'created_at' => now()->subDays($days),
        ]);
    }

    $widget = new RecentUsersWidget();
    $data = $widget();

    expect($data)->toBeInstanceOf(RecentUsersWidgetData::class)
        ->and($data->users)->toHaveCount(5)
        ->and(collect($data->users)->pluck('name')->all())->toBe([
            'User 1', 'User 2', 'User 3', 'User 4', 'User 5',
        ]);
});

it('ships the user details the widget needs', function (): void {
    $user = User::factory()->create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'created_at' => now()->subHour(),
    ]);

    $widget = new RecentUsersWidget();
    $data = $widget();

    expect($data->users)->toBe([[
        'id' => $user->id,
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'created_at' => now()->subHour()->toIso8601String(),
    ]]);
});

it('returns an empty list when there are no users', function (): void {
    $widget = new RecentUsersWidget();
    $data = $widget();

    expect($data->users)->toBe([]);
});
