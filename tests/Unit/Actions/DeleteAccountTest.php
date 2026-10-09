<?php

declare(strict_types=1);

use App\Actions\DeleteAccount;
use App\Domain\Exceptions\LastAdministrator;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

it('deletes the account and records it', function (): void {
    $user = User::factory()->create(['name' => 'Ada', 'email' => 'ada@example.com']);

    resolve(DeleteAccount::class)->handle($user);

    $activity = Activity::query()->where('event', 'account-deleted')->sole();

    expect($user->exists)->toBeFalse()
        ->and($activity->causer_id)->toBe($user->id)
        ->and($activity->getProperty('attributes'))->toBe(['name' => 'Ada', 'email' => 'ada@example.com']);
});

it('refuses to delete the last holder of an administrative role', function (string $role): void {
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate($role));

    expect(fn () => resolve(DeleteAccount::class)->handle($user))
        ->toThrow(LastAdministrator::class, sprintf('The last holder of the [%s] role cannot delete their account.', $role));

    expect($user->fresh())->not->toBeNull();
})->with([User::PANEL_ROLE, User::SUPER_ADMIN_ROLE]);

it('lets a super-admin go while another super-admin remains', function (): void {
    $role = Role::findOrCreate(User::SUPER_ADMIN_ROLE);
    $user = User::factory()->create();
    $user->assignRole($role);
    User::factory()->create()->assignRole($role);

    resolve(DeleteAccount::class)->handle($user);

    expect($user->exists)->toBeFalse();
});

it('checks the last-admin rule after locking the administrative roles', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate(User::PANEL_ROLE));
    User::factory()->create()->assignRole(User::PANEL_ROLE);

    DB::enableQueryLog();
    resolve(DeleteAccount::class)->handle($user);

    $queries = collect(DB::getQueryLog())->pluck('query')->values();
    $lock = $queries->search(fn (string $query): bool => str_contains($query, '"roles"') && str_contains($query, '"name" in'));
    $count = $queries->search(fn (string $query): bool => str_contains($query, 'count(*)'));

    expect($lock)->toBeInt()
        ->and($count)->toBeGreaterThan($lock);
});
