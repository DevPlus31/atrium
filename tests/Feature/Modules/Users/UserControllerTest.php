<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\NavRegistry;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->withoutVite();

    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

function usersModuleAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

it('redirects guests to the login page', function (string $method, string $uri): void {
    $response = $this->{$method}($uri);

    $response->assertRedirectToRoute('login');
})->with([
    'index' => ['get', '/admin/users'],
    'create' => ['get', '/admin/users/create'],
    'store' => ['post', '/admin/users'],
]);

it('forbids authenticated users without the admin role', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin/users');

    $response->assertForbidden();
});

it('forbids admins without the users.view permission', function (): void {
    Role::findByName('admin')->revokePermissionTo('users.view');

    $response = $this->actingAs(usersModuleAdmin())->get(route('admin.users.index'));

    $response->assertForbidden();
});

it('registers the users nav item for permitted admins', function (): void {
    $admin = usersModuleAdmin();

    $navItems = $this->app->make(NavRegistry::class)->itemsFor($admin);
    $navItem = collect($navItems)->firstWhere('label', 'Users');

    expect($navItem)->not->toBeNull()
        ->and($navItem?->href)->toBe(route('admin.users.index'))
        ->and($navItem?->group)->toBe('Management')
        ->and($navItem?->icon)->toBe('users');
});

it('hides the users nav item without the users.view permission', function (): void {
    $user = User::factory()->create();

    $navItems = $this->app->make(NavRegistry::class)->itemsFor($user);

    expect(collect($navItems)->firstWhere('label', 'Users'))->toBeNull();
});

it('renders the users index', function (): void {
    $admin = usersModuleAdmin();
    $other = User::factory()->create(['name' => 'Jane Doe']);

    $response = $this->actingAs($admin)->get(route('admin.users.index'));

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->component('users::index', false)
        ->has('users.data', 2)
        ->has('users.meta')
        ->where('users.meta.per_page', 15)
        ->where('roles', ['admin']));
});

it('ships per-row abilities that forbid self-deletion', function (): void {
    $admin = usersModuleAdmin();
    $other = User::factory()->create(['created_at' => now()->subDay()]);

    $response = $this->actingAs($admin)->get(route('admin.users.index'));

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->where('users.data.0.id', $admin->id)
        ->where('users.data.0.can.update', true)
        ->where('users.data.0.can.delete', false)
        ->where('users.data.1.id', $other->id)
        ->where('users.data.1.can.delete', true));
});

it('applies the search filter to the index', function (): void {
    $admin = usersModuleAdmin();
    User::factory()->create(['name' => 'Alice Wonder', 'email' => 'alice@example.com']);
    User::factory()->create(['name' => 'Bob Builder', 'email' => 'bob@example.com']);

    $response = $this->actingAs($admin)->get(route('admin.users.index', ['filter' => ['search' => 'alice']]));

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->has('users.data', 1)
        ->where('users.data.0.email', 'alice@example.com'));
});

it('applies the role filter as csv values', function (): void {
    $admin = usersModuleAdmin();
    Role::findOrCreate('editor');
    Role::findOrCreate('viewer');

    $editor = User::factory()->create();
    $editor->assignRole('editor');

    $viewer = User::factory()->create();
    $viewer->assignRole('viewer');

    User::factory()->create();

    $response = $this->actingAs($admin)->get(route('admin.users.index', ['filter' => ['role' => 'editor,viewer']]));

    $response->assertOk()->assertInertia(fn ($page) => $page->has('users.data', 2));
});

it('applies the verified filter to the index', function (): void {
    $admin = usersModuleAdmin();
    User::factory()->unverified()->create(['email' => 'unverified@example.com']);

    $verified = $this->actingAs($admin)->get(route('admin.users.index', ['filter' => ['verified' => 'yes']]));
    $unverified = $this->actingAs($admin)->get(route('admin.users.index', ['filter' => ['verified' => 'no']]));

    $verified->assertOk()->assertInertia(fn ($page) => $page
        ->has('users.data', 1)
        ->where('users.data.0.id', $admin->id));

    $unverified->assertOk()->assertInertia(fn ($page) => $page
        ->has('users.data', 1)
        ->where('users.data.0.email', 'unverified@example.com'));
});

it('applies allowed sorts to the index', function (): void {
    $admin = usersModuleAdmin();
    $admin->update(['name' => 'Middle']);
    User::factory()->create(['name' => 'Aaa']);
    User::factory()->create(['name' => 'Zzz']);

    $ascending = $this->actingAs($admin)->get(route('admin.users.index', ['sort' => 'name']));
    $descending = $this->actingAs($admin)->get(route('admin.users.index', ['sort' => '-name']));

    $ascending->assertOk()->assertInertia(fn ($page) => $page->where('users.data.0.name', 'Aaa'));
    $descending->assertOk()->assertInertia(fn ($page) => $page->where('users.data.0.name', 'Zzz'));
});

it('sorts the index by newest first by default', function (): void {
    $admin = usersModuleAdmin();
    User::factory()->create(['name' => 'Older', 'created_at' => now()->subWeek()]);
    $newest = User::factory()->create(['name' => 'Newest', 'created_at' => now()->addHour()]);

    $response = $this->actingAs($admin)->get(route('admin.users.index'));

    $response->assertOk()->assertInertia(fn ($page) => $page->where('users.data.0.id', $newest->id));
});

it('paginates the index and caps per_page at 100', function (): void {
    $admin = usersModuleAdmin();
    User::factory()->count(12)->create();

    $paged = $this->actingAs($admin)->get(route('admin.users.index', ['per_page' => 10, 'page' => 2]));
    $capped = $this->actingAs($admin)->get(route('admin.users.index', ['per_page' => 500]));

    $paged->assertOk()->assertInertia(fn ($page) => $page
        ->has('users.data', 3)
        ->where('users.meta.current_page', 2)
        ->where('users.meta.per_page', 10)
        ->where('users.meta.total', 13));

    $capped->assertOk()->assertInertia(fn ($page) => $page->where('users.meta.per_page', 100));
});

it('renders the create page', function (): void {
    $response = $this->actingAs(usersModuleAdmin())->get(route('admin.users.create'));

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->component('users::create', false)
        ->where('roles', ['admin']));
});

it('forbids admins without the users.create permission', function (): void {
    Role::findByName('admin')->revokePermissionTo('users.create');
    $admin = usersModuleAdmin();

    $this->actingAs($admin)->get(route('admin.users.create'))->assertForbidden();
    $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => 'super-secret-password',
        'password_confirmation' => 'super-secret-password',
    ])->assertForbidden();
});

it('stores a user and redirects with a success flash', function (): void {
    Notification::fake();
    Role::findOrCreate('editor');
    $admin = usersModuleAdmin();

    $response = $this->actingAs($admin)
        ->fromRoute('admin.users.create')
        ->post(route('admin.users.store'), [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'super-secret-password',
            'password_confirmation' => 'super-secret-password',
            'roles' => ['editor'],
        ]);

    $response->assertRedirectToRoute('admin.users.index')
        ->assertSessionHas('success', 'User created.');

    $user = User::query()->where('email', 'new@example.com')->sole();

    expect($user->hasRole('editor'))->toBeTrue()
        ->and(Activity::query()->where('event', 'created')->where('causer_id', $admin->id)->exists())->toBeTrue();
});

it('validates the store request', function (): void {
    $admin = usersModuleAdmin();
    User::factory()->create(['email' => 'taken@example.com']);

    $missing = $this->actingAs($admin)
        ->fromRoute('admin.users.create')
        ->post(route('admin.users.store'), []);

    $missing->assertRedirectToRoute('admin.users.create')
        ->assertSessionHasErrors(['name', 'email', 'password']);

    $invalid = $this->actingAs($admin)
        ->fromRoute('admin.users.create')
        ->post(route('admin.users.store'), [
            'name' => 'New User',
            'email' => 'taken@example.com',
            'password' => 'super-secret-password',
            'password_confirmation' => 'different-password',
            'roles' => ['missing-role'],
        ]);

    $invalid->assertRedirectToRoute('admin.users.create')
        ->assertSessionHasErrors(['email', 'password', 'roles.0']);

    expect(User::query()->count())->toBe(2);
});

it('validates a single field precognitively without side effects', function (): void {
    $admin = usersModuleAdmin();

    $invalid = $this->actingAs($admin)
        ->withHeaders(['Precognition' => 'true', 'Precognition-Validate-Only' => 'email'])
        ->postJson(route('admin.users.store'), ['email' => 'not-an-email']);

    $valid = $this->actingAs($admin)
        ->withHeaders(['Precognition' => 'true', 'Precognition-Validate-Only' => 'email'])
        ->postJson(route('admin.users.store'), ['email' => 'valid@example.com']);

    $invalid->assertStatus(422)
        ->assertHeader('Precognition', 'true')
        ->assertJsonValidationErrors(['email'])
        ->assertJsonMissingValidationErrors(['name', 'password']);

    $valid->assertNoContent()->assertHeader('Precognition-Success', 'true');

    expect(User::query()->count())->toBe(1)
        ->and(Activity::query()->count())->toBe(0);
});

it('renders the edit page', function (): void {
    $admin = usersModuleAdmin();
    $user = User::factory()->create();

    $response = $this->actingAs($admin)->get(route('admin.users.edit', $user));

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->component('users::edit', false)
        ->where('user.id', $user->id)
        ->where('user.email', $user->email)
        ->where('roles', ['admin']));
});

it('forbids admins without the users.update permission', function (): void {
    Role::findByName('admin')->revokePermissionTo('users.update');
    $admin = usersModuleAdmin();
    $user = User::factory()->create();

    $this->actingAs($admin)->get(route('admin.users.edit', $user))->assertForbidden();
    $this->actingAs($admin)->put(route('admin.users.update', $user), [
        'name' => 'Updated',
        'email' => 'updated@example.com',
    ])->assertForbidden();
});

it('updates a user and redirects with a success flash', function (): void {
    Role::findOrCreate('editor');
    $admin = usersModuleAdmin();
    $user = User::factory()->create();

    $response = $this->actingAs($admin)
        ->fromRoute('admin.users.edit', $user)
        ->put(route('admin.users.update', $user), [
            'name' => 'Updated Name',
            'email' => $user->email,
            'roles' => ['editor'],
        ]);

    $response->assertRedirectToRoute('admin.users.index')
        ->assertSessionHas('success', 'User updated.');

    expect($user->refresh()->name)->toBe('Updated Name')
        ->and($user->hasRole('editor'))->toBeTrue()
        ->and(Activity::query()->where('event', 'updated')->where('causer_id', $admin->id)->exists())->toBeTrue();
});

it('validates the update request', function (): void {
    $admin = usersModuleAdmin();
    $user = User::factory()->create();
    User::factory()->create(['email' => 'taken@example.com']);

    $duplicate = $this->actingAs($admin)
        ->fromRoute('admin.users.edit', $user)
        ->put(route('admin.users.update', $user), [
            'name' => 'Updated Name',
            'email' => 'taken@example.com',
        ]);

    $duplicate->assertRedirectToRoute('admin.users.edit', $user)
        ->assertSessionHasErrors(['email']);

    $own = $this->actingAs($admin)
        ->fromRoute('admin.users.edit', $user)
        ->put(route('admin.users.update', $user), [
            'name' => 'Updated Name',
            'email' => $user->email,
        ]);

    $own->assertRedirectToRoute('admin.users.index')
        ->assertSessionDoesntHaveErrors();
});

it('deletes a user and redirects with a success flash', function (): void {
    $admin = usersModuleAdmin();
    $user = User::factory()->create();

    $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $user));

    $response->assertRedirectToRoute('admin.users.index')
        ->assertSessionHas('success', 'User deleted.');

    expect(User::query()->whereKey($user->id)->exists())->toBeFalse()
        ->and(Activity::query()->where('event', 'deleted')->where('causer_id', $admin->id)->exists())->toBeTrue();
});

it('forbids deleting yourself', function (): void {
    $admin = usersModuleAdmin();

    $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $admin));

    $response->assertForbidden();

    expect(User::query()->whereKey($admin->id)->exists())->toBeTrue();
});

it('forbids admins without the users.delete permission', function (): void {
    Role::findByName('admin')->revokePermissionTo('users.delete');
    $admin = usersModuleAdmin();
    $user = User::factory()->create();

    $this->actingAs($admin)->delete(route('admin.users.destroy', $user))->assertForbidden();
});
