<?php

declare(strict_types=1);

use App\Settings\GeneralSettings;
use Inertia\Testing\AssertableInertia;

it('closes sign-up when General settings turn registration off', function (): void {
    $settings = resolve(GeneralSettings::class);
    $settings->registration_open = false;
    $settings->save();

    $this->get(route('register'))->assertNotFound();
    $this->post(route('register.store'), [
        'name' => 'Late Comer',
        'email' => 'late@example.com',
        'password' => 'a-Strong-passw0rd!',
        'password_confirmation' => 'a-Strong-passw0rd!',
    ])->assertNotFound();

    $this->get(route('login'))->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('canRegister', false));
});

it('offers sign-up while registration is open', function (): void {
    $this->get(route('register'))->assertOk();
    $this->get(route('login'))->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('canRegister', true));
});

it('shares the branding settings with every page', function (): void {
    $settings = resolve(GeneralSettings::class);
    $settings->support_email = 'help@example.com';
    $settings->logo_path = 'branding/logo.png';
    $settings->save();

    $this->get(route('login'))->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->where('supportEmail', 'help@example.com')
        ->where('logo', fn (string $url): bool => str_ends_with($url, '/storage/branding/logo.png')));
});
