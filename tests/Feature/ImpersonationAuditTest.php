<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('names the impersonating admin on what they do as someone else', function (): void {
    $admin = adminUser(['name' => 'Ada Admin']);
    $member = User::factory()->create(['email' => 'member@example.com']);

    $this->actingAs($admin)->post(route('admin.users.impersonate', $member))->assertRedirect();

    $this->patch(route('user-profile.update'), ['name' => 'Changed by admin', 'email' => 'member@example.com'])
        ->assertSessionHasNoErrors();

    $activity = Activity::query()->where('event', 'updated')->sole();

    expect($activity->causer_id)->toBe($member->id)
        ->and($activity->getProperty('impersonator'))->toBe(['id' => $admin->id, 'name' => 'Ada Admin']);
});

it('leaves ordinary audit entries untouched', function (): void {
    $member = User::factory()->create(['email' => 'member@example.com']);

    $this->actingAs($member)
        ->patch(route('user-profile.update'), ['name' => 'Changed myself', 'email' => 'member@example.com'])
        ->assertSessionHasNoErrors();

    expect(Activity::query()->where('event', 'updated')->sole()->getProperty('impersonator'))->toBeNull();
});

it('keeps the impersonated member from having their email moved by the admin', function (): void {
    $member = User::factory()->create(['email' => 'member@example.com']);

    $this->actingAs(adminUser())->post(route('admin.users.impersonate', $member))->assertRedirect();

    $this->patch(route('user-profile.update'), ['name' => $member->name, 'email' => 'admin-owned@example.com'])
        ->assertSessionHasErrors('current_password');

    expect($member->refresh()->email)->toBe('member@example.com');
});
