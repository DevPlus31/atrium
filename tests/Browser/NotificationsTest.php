<?php

declare(strict_types=1);

use App\Models\User;
use Tests\Fixtures\Notifications\GreetingNotification;

it('opens the bell, follows a notification and marks it read', function (): void {
    $user = User::factory()->withoutTwoFactor()->create(['name' => 'Ada']);
    $user->notify(new GreetingNotification(url: route('user-profile.edit')));
    $this->actingAs($user);

    visit(route('notifications.index'))
        ->assertSee('1 unread')
        ->click('[data-test="notification-bell"]')
        ->assertSee('Welcome, Ada')
        ->click('[role="dialog"] [data-test="notification"]')
        ->assertPathIs('/settings/profile')
        ->assertNoJavaScriptErrors();

    expect($user->unreadNotifications()->count())->toBe(0);
});

it('marks everything read from the notifications page', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $user->notify(new GreetingNotification());
    $user->notify(new GreetingNotification());
    $this->actingAs($user);

    visit(route('notifications.index'))
        ->assertSee('2 unread')
        ->click('Mark all as read')
        ->assertSee('All notifications marked as read.')
        ->assertSee("You're all caught up.")
        ->assertNoJavaScriptErrors();

    expect($user->unreadNotifications()->count())->toBe(0);
});

it('turns notification emails off from the settings', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $this->actingAs($user);

    visit(route('notification-preferences.edit'))
        ->click('#notify_by_email')
        ->click('[data-test="save-notification-preferences"]')
        ->assertSee('Notification settings saved.')
        ->assertNoJavaScriptErrors();

    expect($user->refresh()->notify_by_email)->toBeFalse();
});
