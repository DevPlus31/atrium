<?php

declare(strict_types=1);

use App\Models\User;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('renders the audit log index with seeded activity', function (): void {
    $admin = adminUser();
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

    activity('users')->causedBy($admin)->event('invitation-revoked')->log('invitation-revoked');

    visit(route('admin.audit.index'))
        ->assertSee('Audit log')
        ->assertSee('user account created')
        ->assertSee('role permissions updated')
        ->assertSee('scheduled maintenance')
        ->assertSee('Invitation revoked')
        ->assertNoJavaScriptErrors();
});
