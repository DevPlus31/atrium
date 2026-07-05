<?php

declare(strict_types=1);

use App\Models\User;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

function auditSmokeAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

it('renders the audit log index with seeded activity', function (): void {
    $admin = auditSmokeAdmin();
    $this->actingAs($admin);

    $subject = User::factory()->create();

    activity('users')
        ->performedOn($subject)
        ->causedBy($admin)
        ->event('created')
        ->withProperties(['attributes' => ['name' => $subject->name]])
        ->log('user account created');

    activity('roles')
        ->causedBy($admin)
        ->event('updated')
        ->log('role permissions updated');

    activity()->log('scheduled maintenance');

    visit(route('admin.audit.index'))
        ->assertSee('Audit log')
        ->assertSee('user account created')
        ->assertSee('role permissions updated')
        ->assertSee('scheduled maintenance')
        ->assertNoJavaScriptErrors();
});
