<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Tests\Fixtures\Notifications\GreetingNotification;

beforeEach(function (): void {
    $this->withoutVite();

    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('shares only the user fields the UI needs, never roles or permissions', function (): void {
    $admin = adminUser(['name' => 'Ada Admin', 'email' => 'ada@example.com']);

    $this->actingAs($admin)->get(route('admin.dashboard.index'))
        ->assertInertia(fn ($page) => $page
            ->where('auth.user', fn ($user): bool => collect($user)->keys()->sort()->values()->all() === [
                'avatar', 'created_at', 'email', 'email_verified_at', 'id', 'name', 'updated_at',
            ])
            ->where('auth.user.id', $admin->id)
            ->where('auth.user.name', 'Ada Admin')
            ->where('impersonation', null));
});

it('shares the unread count, and the latest notifications only when asked', function (): void {
    $user = User::factory()->create();
    $user->notify(new GreetingNotification(body: 'Older'));
    $this->travel(1)->minute();
    $user->notify(new GreetingNotification(body: 'Newer'));
    $user->notifications()->latest()->first()?->markAsRead();

    $this->actingAs($user)->get(route('user-profile.edit'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('unreadNotifications', 1)
            ->missing('recentNotifications')
            ->reloadOnly('recentNotifications', fn (AssertableInertia $reload): AssertableInertia => $reload
                ->has('recentNotifications', 2)
                ->where('recentNotifications.0.body', 'Newer')
                ->whereNot('recentNotifications.0.read_at', null)
                ->where('recentNotifications.1.read_at', null)));
});

it('shares no notifications with guests', function (): void {
    $this->get(route('login'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('unreadNotifications', 0)
            ->reloadOnly('recentNotifications', fn (AssertableInertia $reload): AssertableInertia => $reload
                ->where('recentNotifications', [])));
});
