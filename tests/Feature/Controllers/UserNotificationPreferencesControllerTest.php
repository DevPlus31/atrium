<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia;

it('requires a signed-in user', function (): void {
    $this->get(route('notification-preferences.edit'))->assertRedirectToRoute('login');
    $this->put(route('notification-preferences.update'))->assertRedirectToRoute('login');
});

it('shows whether notifications are emailed', function (): void {
    $user = User::factory()->create(['notify_by_email' => false]);

    $this->actingAs($user)->get(route('notification-preferences.edit'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('user-notification-preferences/edit')
            ->where('notifyByEmail', false));
});

it('turns notification emails off and on', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('notification-preferences.update'), ['notify_by_email' => false])
        ->assertRedirectToRoute('notification-preferences.edit')
        ->assertToast('Notification settings saved.');

    expect($user->refresh()->notify_by_email)->toBeFalse();

    $this->actingAs($user)->put(route('notification-preferences.update'), ['notify_by_email' => true]);

    expect($user->refresh()->notify_by_email)->toBeTrue();
});

it('requires a yes or no', function (mixed $value): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('notification-preferences.update'), ['notify_by_email' => $value])
        ->assertSessionHasErrors('notify_by_email');

    expect($user->refresh()->notify_by_email)->toBeTrue();
})->with([
    'missing' => null,
    'not a boolean' => 'sometimes',
]);
