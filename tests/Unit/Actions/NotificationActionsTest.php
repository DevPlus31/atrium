<?php

declare(strict_types=1);

use App\Actions\MarkAllNotificationsRead;
use App\Actions\MarkNotificationRead;
use App\Actions\UpdateNotificationPreferences;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Tests\Fixtures\Notifications\GreetingNotification;

it('marks one notification read', function (): void {
    $user = User::factory()->create();
    $user->notify(new GreetingNotification());
    $user->notify(new GreetingNotification());

    $notification = DatabaseNotification::query()->firstOrFail();

    resolve(MarkNotificationRead::class)->handle($notification);

    expect($notification->refresh()->read_at?->toIso8601String())->toBe(now()->toIso8601String())
        ->and($user->unreadNotifications()->count())->toBe(1);
});

it("marks all of one user's notifications read", function (): void {
    $user = User::factory()->create();
    $user->notify(new GreetingNotification());
    $user->notify(new GreetingNotification());

    $other = User::factory()->create();
    $other->notify(new GreetingNotification());

    resolve(MarkAllNotificationsRead::class)->handle($user);

    expect($user->unreadNotifications()->count())->toBe(0)
        ->and($other->unreadNotifications()->count())->toBe(1);
});

it('turns notification emails off and on', function (): void {
    $user = User::factory()->create();
    $action = resolve(UpdateNotificationPreferences::class);

    $action->handle($user, false);

    expect($user->refresh()->notify_by_email)->toBeFalse();

    $action->handle($user, true);

    expect($user->refresh()->notify_by_email)->toBeTrue();
});
