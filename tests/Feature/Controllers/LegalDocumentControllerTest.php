<?php

declare(strict_types=1);

use App\Models\User;
use App\Settings\GeneralSettings;
use Inertia\Testing\AssertableInertia;

it('shows each legal document to everyone', function (string $document, string $title): void {
    $this->get(route('legal.show', $document))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('legal-document/show')
            ->where('document.document', $document)
            ->where('document.title', $title)
            ->where('document.html', fn (string $html): bool => str_contains($html, '<h2>')));

    $this->actingAs(User::factory()->create())
        ->get(route('legal.show', $document))
        ->assertOk();
})->with([
    ['terms', 'Terms of Service'],
    ['privacy', 'Privacy Policy'],
    ['cookies', 'Cookie Policy'],
]);

it('fills in the app name and the support address', function (): void {
    config(['app.name' => 'Acme']);
    $settings = resolve(GeneralSettings::class);
    $settings->support_email = 'privacy@acme.test';
    $settings->save();

    $this->get(route('legal.show', 'privacy'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('document.html', fn (string $html): bool => str_contains($html, 'Acme collects')
                && str_contains($html, 'privacy@acme.test')
                && ! str_contains($html, ':app')
                && ! str_contains($html, ':contact')));
});

it('falls back to the address mail is sent from without a support address', function (): void {
    config(['mail.from.address' => 'hello@acme.test']);

    $this->get(route('legal.show', 'terms'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('document.html', fn (string $html): bool => str_contains($html, 'hello@acme.test')));
});

it('renders the tables of the documents', function (): void {
    $this->get(route('legal.show', 'cookies'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('document.html', fn (string $html): bool => str_contains($html, '<table>')));
});

it('shows the document in the visitor’s language', function (): void {
    $this->withUnencryptedCookie('locale', 'fr')
        ->get(route('legal.show', 'privacy'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('document.title', 'Politique de confidentialité')
            ->where('document.html', fn (string $html): bool => str_contains($html, 'Qui sommes-nous')));
});

it('answers 404 for an unknown document', function (): void {
    $this->get('/legal/imprint')->assertNotFound();
});
