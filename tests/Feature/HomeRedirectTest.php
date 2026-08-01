<?php

declare(strict_types=1);

use App\Models\User;

it('redirects the root to the dashboard', function (): void {
    $this->get('/')->assertRedirect(route('dashboard'));
});

it('redirects the dashboard into the admin panel', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('admin.dashboard.index'));
});

it('sends guests from the dashboard to the login page', function (): void {
    $this->get(route('dashboard'))->assertRedirectToRoute('login');
});
