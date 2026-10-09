<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\NavRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->withoutVite();

    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('redirects guests to the login page', function (string $method, string $uri): void {
    $response = $this->{$method}($uri);

    $response->assertRedirectToRoute('login');
})->with([
    'index' => ['get', '/admin/roles'],
    'create' => ['get', '/admin/roles/create'],
    'store' => ['post', '/admin/roles'],
    'edit' => ['get', '/admin/roles/1/edit'],
    'update' => ['put', '/admin/roles/1'],
    'destroy' => ['delete', '/admin/roles/1'],
]);

it('forbids authenticated users without the admin role', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin/roles');

    $response->assertForbidden();
});

it('forbids admins without the roles.view permission', function (): void {
    Role::findByName('admin')->revokePermissionTo('roles.view');

    $response = $this->actingAs(adminUser())->get(route('admin.roles.index'));

    $response->assertForbidden();
});

it('registers the roles nav item for permitted admins', function (): void {
    $admin = adminUser();

    $navItems = $this->app->make(NavRegistry::class)->itemsFor($admin);
    $navItem = collect($navItems)->firstWhere('label', 'Roles');

    expect($navItem)->not->toBeNull()
        ->and($navItem?->href)->toBe(route('admin.roles.index'))
        ->and($navItem?->group)->toBe('Management')
        ->and($navItem?->icon)->toBe('shield');
});

it('hides the roles nav item without the roles.view permission', function (): void {
    $user = User::factory()->create();

    $navItems = $this->app->make(NavRegistry::class)->itemsFor($user);

    expect(collect($navItems)->firstWhere('label', 'Roles'))->toBeNull();
});

it('renders the roles index', function (): void {
    $admin = adminUser();

    $editor = Role::findOrCreate('editor');
    $editor->givePermissionTo('users.view');

    $response = $this->actingAs($admin)->get(route('admin.roles.index'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('roles::index')
        ->has('roles.data', 2)
        ->has('roles.meta')
        ->where('roles.meta.per_page', 15)
        ->where('roles.data.0.name', 'admin')
        ->where('roles.data.0.is_system', true)
        ->where('roles.data.0.users_count', 1)
        ->where('roles.data.1.name', 'editor')
        ->where('roles.data.1.is_system', false)
        ->where('roles.data.1.users_count', 0)
        ->where('roles.data.1.permissions', ['users.view'])
        ->missing('permissions'));
});

it('ships per-row abilities that forbid changing system roles', function (): void {
    $admin = adminUser();
    Role::findOrCreate('editor');

    $response = $this->actingAs($admin)->get(route('admin.roles.index'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->where('roles.data.0.name', 'admin')
        ->where('roles.data.0.can.update', false)
        ->where('roles.data.0.can.delete', false)
        ->where('roles.data.1.name', 'editor')
        ->where('roles.data.1.can.update', true)
        ->where('roles.data.1.can.delete', true));
});

it('applies the search filter to the index', function (): void {
    $admin = adminUser();
    Role::findOrCreate('content-editor');
    Role::findOrCreate('moderator');

    $response = $this->actingAs($admin)->get(route('admin.roles.index', ['filter' => ['search' => 'editor']]));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->has('roles.data', 1)
        ->where('roles.data.0.name', 'content-editor'));
});

it('applies allowed sorts to the index', function (): void {
    $admin = adminUser();
    Role::findOrCreate('alpha');
    Role::findOrCreate('zebra');

    $ascending = $this->actingAs($admin)->get(route('admin.roles.index', ['sort' => 'name']));
    $descending = $this->actingAs($admin)->get(route('admin.roles.index', ['sort' => '-name']));

    $ascending->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('roles.data.0.name', 'admin'));
    $descending->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('roles.data.0.name', 'zebra'));
});

it('sorts the index by name by default', function (): void {
    $admin = adminUser();
    Role::findOrCreate('zebra');
    Role::findOrCreate('alpha');

    $response = $this->actingAs($admin)->get(route('admin.roles.index'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->where('roles.data.0.name', 'admin')
        ->where('roles.data.1.name', 'alpha')
        ->where('roles.data.2.name', 'zebra'));
});

it('paginates the index and caps per_page at 100', function (): void {
    $admin = adminUser();

    foreach (range(1, 12) as $index) {
        Role::findOrCreate('role-'.$index);
    }

    $paged = $this->actingAs($admin)->get(route('admin.roles.index', ['per_page' => 10, 'page' => 2]));
    $capped = $this->actingAs($admin)->get(route('admin.roles.index', ['per_page' => 500]));

    $paged->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->has('roles.data', 3)
        ->where('roles.meta.current_page', 2)
        ->where('roles.meta.per_page', 10)
        ->where('roles.meta.total', 13));

    $capped->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('roles.meta.per_page', 100));
});

it('renders the create page', function (): void {
    $response = $this->actingAs(adminUser())->get(route('admin.roles.create'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('roles::create')
        ->where('permissions', fn (Collection $permissions) => collect($permissions)->contains('roles.create')));
});

it('forbids admins without the roles.create permission', function (): void {
    Role::findByName('admin')->revokePermissionTo('roles.create');
    $admin = adminUser();

    $this->actingAs($admin)->get(route('admin.roles.create'))->assertForbidden();
    $this->actingAs($admin)->post(route('admin.roles.store'), [
        'name' => 'editor',
        'permissions' => [],
    ])->assertForbidden();
});

it('stores a role and redirects with a success flash', function (): void {
    $admin = adminUser();

    $response = $this->actingAs($admin)
        ->fromRoute('admin.roles.create')
        ->post(route('admin.roles.store'), [
            'name' => 'editor',
            'permissions' => ['users.view', 'users.update'],
        ]);

    $response->assertRedirectToRoute('admin.roles.index')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Role created.']);

    /** @var Role $role */
    $role = Role::findByName('editor');

    expect($role->permissions()->pluck('name')->all())->toEqualCanonicalizing(['users.view', 'users.update'])
        ->and(Activity::query()->where('event', 'created')->where('causer_id', $admin->id)->exists())->toBeTrue();
});

it('validates the store request', function (): void {
    $admin = adminUser();

    $missing = $this->actingAs($admin)
        ->fromRoute('admin.roles.create')
        ->post(route('admin.roles.store'), []);

    $missing->assertRedirectToRoute('admin.roles.create')
        ->assertSessionHasErrors(['name']);

    $invalid = $this->actingAs($admin)
        ->fromRoute('admin.roles.create')
        ->post(route('admin.roles.store'), [
            'name' => 'admin',
            'permissions' => ['missing.permission'],
        ]);

    $invalid->assertRedirectToRoute('admin.roles.create')
        ->assertSessionHasErrors(['name', 'permissions.0']);

    expect(Role::query()->count())->toBe(1);
});

it('validates a single field precognitively without side effects', function (): void {
    $admin = adminUser();

    $invalid = $this->actingAs($admin)
        ->withHeaders(['Precognition' => 'true', 'Precognition-Validate-Only' => 'name'])
        ->postJson(route('admin.roles.store'), ['name' => 'admin']);

    $valid = $this->actingAs($admin)
        ->withHeaders(['Precognition' => 'true', 'Precognition-Validate-Only' => 'name'])
        ->postJson(route('admin.roles.store'), ['name' => 'editor']);

    $invalid->assertStatus(422)
        ->assertHeader('Precognition', 'true')
        ->assertJsonValidationErrors(['name'])
        ->assertJsonMissingValidationErrors(['permissions']);

    $valid->assertNoContent()->assertHeader('Precognition-Success', 'true');

    expect(Role::query()->count())->toBe(1)
        ->and(Activity::query()->count())->toBe(0);
});

it('renders the edit page', function (): void {
    $admin = adminUser();

    $role = Role::findOrCreate('editor');
    $role->givePermissionTo('users.view');

    $response = $this->actingAs($admin)->get(route('admin.roles.edit', $role));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('roles::edit')
        ->where('role.id', (string) $role->id)
        ->where('role.name', 'editor')
        ->where('role.permissions', ['users.view'])
        ->where('permissions', fn (Collection $permissions) => collect($permissions)->contains('roles.update')));
});

it('forbids admins without the roles.update permission', function (): void {
    Role::findByName('admin')->revokePermissionTo('roles.update');
    $admin = adminUser();
    $role = Role::findOrCreate('editor');

    $this->actingAs($admin)->get(route('admin.roles.edit', $role))->assertForbidden();
    $this->actingAs($admin)->put(route('admin.roles.update', $role), [
        'name' => 'publisher',
        'permissions' => [],
    ])->assertForbidden();
});

it('updates a role and redirects with a success flash', function (): void {
    $admin = adminUser();
    $role = Role::findOrCreate('editor');

    $response = $this->actingAs($admin)
        ->fromRoute('admin.roles.edit', $role)
        ->put(route('admin.roles.update', $role), [
            'name' => 'publisher',
            'permissions' => ['users.view'],
        ]);

    $response->assertRedirectToRoute('admin.roles.index')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Role updated.']);

    $role->refresh();

    expect($role->name)->toBe('publisher')
        ->and($role->permissions()->pluck('name')->all())->toBe(['users.view'])
        ->and(Activity::query()->where('event', 'updated')->where('causer_id', $admin->id)->exists())->toBeTrue();
});

it('validates the update request', function (): void {
    $admin = adminUser();
    $role = Role::findOrCreate('editor');
    Role::findOrCreate('publisher');

    $duplicate = $this->actingAs($admin)
        ->fromRoute('admin.roles.edit', $role)
        ->put(route('admin.roles.update', $role), [
            'name' => 'publisher',
            'permissions' => [],
        ]);

    $duplicate->assertRedirectToRoute('admin.roles.edit', $role)
        ->assertSessionHasErrors(['name']);

    $own = $this->actingAs($admin)
        ->fromRoute('admin.roles.edit', $role)
        ->put(route('admin.roles.update', $role), [
            'name' => 'editor',
            'permissions' => [],
        ]);

    $own->assertRedirectToRoute('admin.roles.index')
        ->assertSessionDoesntHaveErrors();
});

it('rejects renaming a system role', function (): void {
    $admin = superAdminUser();
    $role = Role::findByName('admin');

    $response = $this->actingAs($admin)
        ->fromRoute('admin.roles.edit', $role)
        ->put(route('admin.roles.update', $role), [
            'name' => 'administrator',
            'permissions' => [],
        ]);

    $response->assertRedirectToRoute('admin.roles.edit', $role)
        ->assertSessionHasErrors(['name' => 'System roles cannot be renamed.']);

    expect(Role::query()->where('name', 'admin')->exists())->toBeTrue();
});

it('lets a super-admin update the permissions of a system role', function (): void {
    $admin = superAdminUser();
    $role = Role::findByName('admin');

    $response = $this->actingAs($admin)
        ->fromRoute('admin.roles.edit', $role)
        ->put(route('admin.roles.update', $role), [
            'name' => 'admin',
            'permissions' => ['roles.view', 'roles.update'],
        ]);

    $response->assertRedirectToRoute('admin.roles.index')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Role updated.']);

    expect($role->permissions()->pluck('name')->all())->toEqualCanonicalizing(['roles.view', 'roles.update']);
});

it('deletes a role and redirects with a success flash', function (): void {
    $admin = adminUser();
    $role = Role::findOrCreate('editor');

    $response = $this->actingAs($admin)->delete(route('admin.roles.destroy', $role));

    $response->assertRedirectToRoute('admin.roles.index')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Role deleted.']);

    expect(Role::query()->whereKey($role->id)->exists())->toBeFalse()
        ->and(Activity::query()->where('event', 'deleted')->where('causer_id', $admin->id)->exists())->toBeTrue();
});

it('forbids deleting a system role', function (): void {
    $admin = adminUser();
    $role = Role::findByName('admin');

    $response = $this->actingAs($admin)->delete(route('admin.roles.destroy', $role));

    $response->assertForbidden();

    expect(Role::query()->where('name', 'admin')->exists())->toBeTrue();
});

it('forbids admins without the roles.delete permission', function (): void {
    Role::findByName('admin')->revokePermissionTo('roles.delete');
    $admin = adminUser();
    $role = Role::findOrCreate('editor');

    $this->actingAs($admin)->delete(route('admin.roles.destroy', $role))->assertForbidden();
});

it('forbids admins changing a system role', function (string $name): void {
    Role::findOrCreate('super-admin');
    $admin = adminUser();
    $role = Role::findByName($name);

    $this->actingAs($admin)->get(route('admin.roles.edit', $role))->assertForbidden();

    $this->actingAs($admin)->put(route('admin.roles.update', $role), [
        'name' => $name,
        'permissions' => [],
    ])->assertForbidden();

    expect($admin->can('update', $role))->toBeFalse();
})->with(['admin', 'super-admin']);

it('rejects granting a permission the admin does not hold', function (string $route): void {
    Role::findByName('admin')->revokePermissionTo('audit.view');
    $admin = adminUser();
    $role = Role::findOrCreate('editor');

    $payload = ['name' => 'editor', 'permissions' => ['users.view', 'audit.view']];

    $response = $route === 'store'
        ? $this->actingAs($admin)->post(route('admin.roles.store'), [...$payload, 'name' => 'auditor'])
        : $this->actingAs($admin)->put(route('admin.roles.update', $role), $payload);

    $response->assertSessionHasErrors(['permissions.1' => 'You cannot grant the audit.view permission.']);

    expect(Role::query()->whereHas('permissions', fn (Builder $query) => $query->where('name', 'audit.view'))->pluck('name')->all())
        ->toBe([]);
})->with(['store', 'update']);

it('keeps permissions a role already has even when the admin does not hold them', function (): void {
    Role::findByName('admin')->revokePermissionTo('audit.view');
    $admin = adminUser();
    $role = Role::findOrCreate('auditor');
    $role->givePermissionTo('audit.view');

    $response = $this->actingAs($admin)->put(route('admin.roles.update', $role), [
        'name' => 'auditors',
        'permissions' => ['audit.view', 'users.view'],
    ]);

    $response->assertSessionDoesntHaveErrors();

    expect($role->refresh()->name)->toBe('auditors')
        ->and($role->permissions()->pluck('name')->all())->toEqualCanonicalizing(['audit.view', 'users.view']);
});
