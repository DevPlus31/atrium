<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
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

expect()->extend('toBeOne', fn () => $this->toBe(1));

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
