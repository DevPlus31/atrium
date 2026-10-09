<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Testing\AssertableInertia;
use Tests\Fixtures\Notifications\GreetingNotification;

it('requires a verified, signed-in user', function (): void {
    $this->get(route('notifications.index'))->assertRedirectToRoute('login');
    $this->patch(route('notifications.read-all'))->assertRedirectToRoute('login');

    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('notifications.index'))
        ->assertRedirectToRoute('verification.notice');
});

it("lists the user's notifications newest first", function (): void {
    $user = User::factory()->create(['name' => 'Ada']);
    $user->notify(new GreetingNotification(body: 'First'));
    $this->travel(1)->minute();
    $user->notify(new GreetingNotification(body: 'Second'));
    User::factory()->create()->notify(new GreetingNotification(body: 'Someone else'));

    $this->actingAs($user)->get(route('notifications.index'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('user-notifications/index')
            ->has('notifications.data', 2)
            ->where('notifications.data.0.body', 'Second')
            ->where('notifications.data.0.title', 'Welcome, Ada')
            ->where('notifications.data.0.read_at', null)
            ->where('notifications.data.1.body', 'First'));
});

it('loads older notifications page by page', function (): void {
    $user = User::factory()->create();

    foreach (range(1, 25) as $minute) {
        $user->notify(new GreetingNotification(body: sprintf('Note %d', $minute)));
        $this->travel(1)->minute();
    }

    $this->actingAs($user)->get(route('notifications.index'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('notifications.data', 20)
            ->where('notifications.data.0.body', 'Note 25'));

    $this->actingAs($user)->get(route('notifications.index', ['page' => 2]))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('notifications.data', 5)
            ->where('notifications.data.4.body', 'Note 1'));
});

it('marks a notification read and follows its link', function (): void {
    $user = User::factory()->create();
    $user->notify(new GreetingNotification(url: route('user-profile.edit')));

    $notification = DatabaseNotification::query()->sole();

    $this->actingAs($user)
        ->patch(route('notifications.update', $notification->id))
        ->assertRedirect(route('user-profile.edit'));

    expect($notification->refresh()->read_at)->not->toBeNull();
});

it('goes back when the notification has no link, or one outside the app', function (?string $url): void {
    $user = User::factory()->create();
    $user->notify(new GreetingNotification(url: $url));

    $notification = DatabaseNotification::query()->sole();

    $this->actingAs($user)
        ->from(route('notifications.index'))
        ->patch(route('notifications.update', $notification->id))
        ->assertRedirect(route('notifications.index'));

    expect($notification->refresh()->read_at)->not->toBeNull();
})->with([
    'no link' => null,
    'another site' => 'https://evil.example.com/phish',
]);

it("cannot read another user's notification", function (): void {
    $owner = User::factory()->create();
    $owner->notify(new GreetingNotification());

    $notification = DatabaseNotification::query()->sole();

    $this->actingAs(User::factory()->create())
        ->patch(route('notifications.update', $notification->id))
        ->assertNotFound();

    expect($notification->refresh()->read_at)->toBeNull();
});

it('marks every unread notification read', function (): void {
    $user = User::factory()->create();
    $user->notify(new GreetingNotification());
    $user->notify(new GreetingNotification());

    $other = User::factory()->create();
    $other->notify(new GreetingNotification());

    $this->actingAs($user)
        ->from(route('notifications.index'))
        ->patch(route('notifications.read-all'))
        ->assertRedirect(route('notifications.index'))
        ->assertToast('All notifications marked as read.');

    expect($user->unreadNotifications()->count())->toBe(0)
        ->and($other->unreadNotifications()->count())->toBe(1);
});

it('is a 404 for a malformed notification id', function (): void {
    $this->actingAs(User::factory()->create())
        ->patch('/notifications/not-a-uuid')
        ->assertNotFound();
});
