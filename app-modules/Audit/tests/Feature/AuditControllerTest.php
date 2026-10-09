<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\NavRegistry;
use Inertia\Testing\AssertableInertia;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('keeps guests and non-admins out', function (): void {
    assertAdminOnly('get', route('admin.audit.index'));
});

it('forbids admins without the audit.view permission', function (): void {
    $response = $this->actingAs(adminWithout('audit.view'))->get(route('admin.audit.index'));

    $response->assertForbidden();
});

it('registers the audit log nav item first in the system group for permitted admins', function (): void {
    $admin = adminUser();

    $navItems = $this->app->make(NavRegistry::class)->itemsFor($admin);
    $navItem = collect($navItems)->firstWhere('label', 'Audit log');
    $systemLabels = collect($navItems)->where('group', 'System')->pluck('label')->values()->all();

    expect($navItem)->not->toBeNull()
        ->and($navItem?->href)->toBe(route('admin.audit.index'))
        ->and($navItem?->group)->toBe('System')
        ->and($navItem?->icon)->toBe('history')
        ->and($systemLabels)->toBe(['Audit log', 'Pulse', 'Horizon', 'Logs']);
});

it('hides the audit log nav item without the audit.view permission', function (): void {
    $user = User::factory()->create();

    $navItems = $this->app->make(NavRegistry::class)->itemsFor($user);

    expect(collect($navItems)->firstWhere('label', 'Audit log'))->toBeNull();
});

it('renders the audit log index with the causer, subject and changes of each row', function (): void {
    $admin = adminUser();
    $subject = User::factory()->create();

    activity('users')
        ->performedOn($subject)
        ->causedBy($admin)
        ->event('created')
        ->withProperties(['attributes' => ['name' => $subject->name, 'email' => $subject->email]])
        ->log('created');

    $response = $this->actingAs($admin)->get(route('admin.audit.index'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('audit::index')
        ->has('activities.data', 1)
        ->has('activities.meta')
        ->where('activities.meta.per_page', 15)
        ->where('activities.data.0.log_name', 'users')
        ->where('activities.data.0.event', 'created')
        ->where('activities.data.0.description', 'created')
        ->where('activities.data.0.causer.name', $admin->name)
        ->where('activities.data.0.causer.email', $admin->email)
        ->where('activities.data.0.subject_type', 'User')
        ->where('activities.data.0.subject_id', $subject->id)
        ->where('activities.data.0.changes.attributes.name', $subject->name)
        ->where('activities.data.0.changes.attributes.email', $subject->email)
        ->where('logNames', ['users'])
        ->where('events', ['created']));
});

it('renders rows without a causer or subject', function (): void {
    $admin = adminUser();

    activity()->log('scheduled maintenance');

    $response = $this->actingAs($admin)->get(route('admin.audit.index'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->has('activities.data', 1)
        ->where('activities.data.0.log_name', 'default')
        ->where('activities.data.0.event', null)
        ->where('activities.data.0.description', 'scheduled maintenance')
        ->where('activities.data.0.causer', null)
        ->where('activities.data.0.subject_type', null)
        ->where('activities.data.0.subject_id', null)
        ->where('activities.data.0.changes', []));
});

it('applies the search filter to the description', function (): void {
    $admin = adminUser();

    activity('users')->causedBy($admin)->log('user created');
    activity('roles')->causedBy($admin)->log('role deleted');

    $response = $this->actingAs($admin)->get(route('admin.audit.index', ['filter' => ['search' => 'role deleted']]));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->has('activities.data', 1)
        ->where('activities.data.0.description', 'role deleted'));
});

it('applies the search filter to the causer name', function (): void {
    $admin = adminUser();
    $causer = User::factory()->create(['name' => 'Zelda Zonneveld']);

    activity('users')->causedBy($causer)->log('user created');
    activity('users')->causedBy($admin)->log('user updated');
    activity('users')->log('user pruned');

    $response = $this->actingAs($admin)->get(route('admin.audit.index', ['filter' => ['search' => 'Zelda']]));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->has('activities.data', 1)
        ->where('activities.data.0.description', 'user created')
        ->where('activities.data.0.causer.name', 'Zelda Zonneveld'));
});

it('filters by log name including comma-separated values', function (): void {
    $admin = adminUser();

    activity('users')->log('user created');
    activity('roles')->log('role created');
    activity('system')->log('cache cleared');

    $single = $this->actingAs($admin)->get(route('admin.audit.index', ['filter' => ['log_name' => 'users']]));
    $multiple = $this->actingAs($admin)->get(route('admin.audit.index', ['filter' => ['log_name' => 'users,roles']]));

    $single->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->has('activities.data', 1)
        ->where('activities.data.0.log_name', 'users'));

    $multiple->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->has('activities.data', 2));
});

it('filters by event including comma-separated values', function (): void {
    $admin = adminUser();

    activity('users')->event('created')->log('user created');
    activity('users')->event('updated')->log('user updated');
    activity('users')->event('deleted')->log('user deleted');

    $single = $this->actingAs($admin)->get(route('admin.audit.index', ['filter' => ['event' => 'created']]));
    $multiple = $this->actingAs($admin)->get(route('admin.audit.index', ['filter' => ['event' => 'created,deleted']]));

    $single->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->has('activities.data', 1)
        ->where('activities.data.0.event', 'created'));

    $multiple->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->has('activities.data', 2));
});

it('applies allowed sorts to the index', function (): void {
    $admin = adminUser();

    activity('users')->log('older entry');
    activity('users')->log('newer entry');

    Activity::query()->where('description', 'older entry')->update(['created_at' => now()->subDay()]);

    $ascending = $this->actingAs($admin)->get(route('admin.audit.index', ['sort' => 'created_at']));
    $descending = $this->actingAs($admin)->get(route('admin.audit.index', ['sort' => '-created_at']));

    $ascending->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('activities.data.0.description', 'older entry'));
    $descending->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('activities.data.0.description', 'newer entry'));
});
