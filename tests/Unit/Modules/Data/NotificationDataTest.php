<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Data\NotificationData;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

it('reads the stored message', function (): void {
    $user = User::factory()->create();
    $notification = $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'example',
        'data' => ['title' => 'Hello', 'body' => 'There', 'url' => 'https://atrium.test', 'action' => 'Open'],
        'read_at' => now(),
    ]);

    expect(NotificationData::fromModel($notification)->toArray())->toBe([
        'id' => $notification->id,
        'title' => 'Hello',
        'body' => 'There',
        'url' => 'https://atrium.test',
        'action' => 'Open',
        'read_at' => now()->toIso8601String(),
        'created_at' => now()->toIso8601String(),
    ]);
});

it('tolerates rows written by other notifications', function (): void {
    $user = User::factory()->create();
    $notification = $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'legacy',
        'data' => ['message' => 'No title here', 'body' => ['not', 'text']],
    ]);

    $data = NotificationData::fromModel(DatabaseNotification::query()->findOrFail($notification->id));

    expect($data->title)->toBe('')
        ->and($data->body)->toBeNull()
        ->and($data->url)->toBeNull()
        ->and($data->read_at)->toBeNull();
});
