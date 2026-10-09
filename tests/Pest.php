<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\IndexQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

// Xdebug coverage instrumentation slows browser tests well past the default
// 5s assertion timeout; a higher ceiling keeps the coverage gate deterministic.
pest()->browser()->timeout(15000);

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function (): void {
        Str::createRandomStringsNormally();
        Str::createUuidsNormally();
        Http::preventStrayRequests();
        Process::preventStrayProcesses();
        Sleep::fake();

        $this->freezeTime();
    })
    ->in('Browser', 'Feature', 'Unit', '../app-modules/*/tests');

// With `composer run dev` running, public/hot points pages at the Vite dev
// server: browser tests would exercise HMR and a CSP widened for it instead
// of the production build. Fail fast rather than test the wrong thing.
pest()->beforeEach(function (): void {
    throw_if(is_file(public_path('hot')), RuntimeException::class, 'Stop `composer run dev` (public/hot exists) and run `bun run build`: browser tests run against the production build.');
})->in('Browser', '../app-modules/*/tests/Browser');

// Feature tests assert Inertia responses, not the built assets.
pest()->beforeEach(function (): void {
    $this->withoutVite();
})->in('Feature', '../app-modules/*/tests/Feature');

expect()->extend('toBeOne', fn () => $this->toBe(1));

/**
 * The toast an action flashed for the next page (App\Modules\Toast).
 */
TestResponse::macro('assertToast', function (string $message, string $type = 'success'): TestResponse {
    /** @var TestResponse<Response> $this */
    return $this->assertInertiaFlash('toast', ['type' => $type, 'message' => $message]);
});

/**
 * A user holding the panel-entry admin role (and, after
 * admin:sync-permissions, every permission the modules grant to it).
 *
 * @param  array<string, mixed>  $attributes
 */
function adminUser(array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->assignRole('admin');

    return $user;
}

/**
 * An admin whose role lacks the given permission(s): revoked from the admin
 * role first, so the new user never holds them.
 *
 * @param  string|list<string>  $permissions
 * @param  array<string, mixed>  $attributes
 */
function adminWithout(string|array $permissions, array $attributes = []): User
{
    Role::findByName(User::PANEL_ROLE)->revokePermissionTo($permissions);

    return adminUser($attributes);
}

/**
 * An admin-area endpoint: guests are sent to sign in, signed-in users
 * without the admin role are refused.
 *
 * @param  array<string, mixed>  $data
 */
function assertAdminOnly(string $method, string $url, array $data = []): void
{
    test()->{$method}($url, $data)->assertRedirectToRoute('login');

    test()->actingAs(User::factory()->create())->{$method}($url, $data)->assertForbidden();
}

/**
 * Selections every bulk-delete endpoint must reject: nothing, not a list,
 * and more than a page of rows. A function rather than a named dataset:
 * Pest scopes datasets to tests/, and module tests live outside it.
 *
 * @return array<string, array{mixed}>
 */
function invalidBulkSelections(): array
{
    return [
        'nothing selected' => [[]],
        'not a list' => ['all'],
        'more than a page' => [array_map(strval(...), range(1, 101))],
    ];
}

/**
 * An image every upload rule accepts (photo, logo): a 200×200 PNG or JPEG.
 */
function validImage(string $name = 'image.png'): UploadedFile
{
    return UploadedFile::fake()->image($name, 200, 200);
}

/**
 * An admin who also holds the super-admin role (Gate::before bypass).
 *
 * @param  array<string, mixed>  $attributes
 */
function superAdminUser(array $attributes = []): User
{
    $user = adminUser($attributes);
    $user->assignRole(Role::findOrCreate(User::SUPER_ADMIN_ROLE));

    return $user;
}

/**
 * An index query as a request with these query-string parameters
 * (`filter`, `sort`, `per_page`, …) would build it.
 *
 * @template TQuery of IndexQuery
 *
 * @param  class-string<TQuery>  $class
 * @param  array<string, mixed>  $query
 * @return TQuery
 */
function indexQuery(string $class, array $query = []): IndexQuery
{
    return new $class(Request::create('/', 'GET', $query));
}

/**
 * The page's `can.<ability>` prop is true for an admin and turns false once
 * the admin role loses the permission behind it.
 */
function assertPageAbilityFollowsPermission(string $route, string $ability, string $permission): void
{
    $admin = adminUser();

    test()->actingAs($admin)->get(route($route))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('can.'.$ability, true));

    Role::findByName('admin')->revokePermissionTo($permission);

    test()->actingAs($admin->refresh())->get(route($route))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('can.'.$ability, false));
}
