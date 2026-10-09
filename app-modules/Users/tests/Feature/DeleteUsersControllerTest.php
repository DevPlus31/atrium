<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('deletes the selected users', function (): void {
    $admin = adminUser();
    $users = User::factory()->count(3)->create();

    $this->actingAs($admin)
        ->delete(route('admin.users.bulk-destroy'), ['ids' => $users->pluck('id')->all()])
        ->assertRedirectToRoute('admin.users.index')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '3 users deleted.']);

    expect(User::query()->whereKey($users->pluck('id'))->exists())->toBeFalse()
        ->and(Activity::query()->where('log_name', 'users')->where('event', 'deleted')->count())->toBe(3);
});

it('leaves out the users the admin may not delete and says so', function (): void {
    $admin = adminUser();
    $other = User::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.users.bulk-destroy'), ['ids' => [$other->id, $admin->id, 'gone-user-id']])
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '1 user deleted. 2 could not be deleted.']);

    expect(User::query()->whereKey($other->id)->exists())->toBeFalse()
        ->and(User::query()->whereKey($admin->id)->exists())->toBeTrue();
});

it('is forbidden when none of the selection may be deleted', function (): void {
    Role::findByName('admin')->revokePermissionTo('users.delete');
    $user = User::factory()->create();

    $this->actingAs(adminUser())
        ->delete(route('admin.users.bulk-destroy'), ['ids' => [$user->id]])
        ->assertForbidden();

    expect(User::query()->whereKey($user->id)->exists())->toBeTrue();
});

it('validates the selection', function (mixed $ids): void {
    $this->actingAs(adminUser())
        ->delete(route('admin.users.bulk-destroy'), ['ids' => $ids])
        ->assertSessionHasErrors('ids');
})->with([
    'nothing' => [[]],
    'not a list' => ['all'],
    'more than a page' => [array_map(strval(...), range(1, 101))],
]);

it('keeps members out', function (): void {
    $this->actingAs(User::factory()->create())
        ->delete(route('admin.users.bulk-destroy'), ['ids' => ['x']])
        ->assertForbidden();
});

it('redirects guests to the login page', function (): void {
    $this->delete(route('admin.users.bulk-destroy'), ['ids' => ['x']])->assertRedirectToRoute('login');
});
