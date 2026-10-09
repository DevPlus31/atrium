<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Support\SessionKey;

it('encrypts the browser history of every page', function (): void {
    $page = $this->actingAs(User::factory()->create())->get(route('user-profile.edit'))->viewData('page');

    expect($page['encryptHistory'] ?? false)->toBeTrue();
});

it('clears the browser history on logout', function (): void {
    $this->actingAs(User::factory()->create())
        ->post(route('logout'))
        ->assertSessionHas(SessionKey::CLEAR_HISTORY, true);

    $page = $this->get(route('login'))->viewData('page');

    expect($page['clearHistory'] ?? false)->toBeTrue();
});

it('clears the browser history when an account deletes itself', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->delete(route('user.destroy'), ['password' => 'password'])
        ->assertSessionHas(SessionKey::CLEAR_HISTORY, true);
});

it('clears the browser history on the first page after impersonation starts and ends', function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
    $member = User::factory()->create();

    $this->actingAs(adminUser())->post(route('admin.users.impersonate', $member));

    expect($this->get(route('user-profile.edit'))->viewData('page')['clearHistory'] ?? false)->toBeTrue()
        ->and($this->get(route('user-profile.edit'))->viewData('page')['clearHistory'] ?? false)->toBeFalse();

    $this->post(route('impersonation.leave'));

    expect($this->get(route('admin.users.index'))->viewData('page')['clearHistory'] ?? false)->toBeTrue();
});
