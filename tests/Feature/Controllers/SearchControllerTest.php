<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Data\SearchResultData;
use App\Modules\SearchRegistry;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('is for signed-in panel users', function (): void {
    $this->getJson(route('search', ['q' => 'ada']))->assertUnauthorized();

    $this->actingAs(User::factory()->create())
        ->getJson(route('search', ['q' => 'ada']))
        ->assertForbidden();
});

it('needs at least two characters', function (?string $term): void {
    $this->actingAs(adminUser())
        ->getJson(route('search', ['q' => $term]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('q');
})->with([null, 'a', ' ', 'way too long '.str_repeat('x', 100)]);

it('answers with the groups the modules found', function (): void {
    $admin = adminUser();
    resolve(SearchRegistry::class)->add(
        module: 'users',
        label: 'Echo',
        searcher: fn (string $term): array => [new SearchResultData(title: $term, description: null, url: 'https://atrium.test')],
    );

    $this->actingAs($admin)
        ->getJson(route('search', ['q' => '  ada  ']))
        ->assertOk()
        ->assertJsonPath('groups.0.label', 'Echo')
        ->assertJsonPath('groups.0.results.0.title', 'ada');
});
