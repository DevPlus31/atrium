<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->withoutVite();

    Route::middleware('web')->group(function (): void {
        Route::get('_test/abort/{status}', fn (int $status) => abort($status));
        Route::get('_test/broken', fn () => throw new RuntimeException('Boom.'));
        Route::post('_test/expired', fn () => throw new TokenMismatchException());
    });
});

it('renders unknown pages as the branded error page with shared data', function (): void {
    $this->get('/this-page-does-not-exist')
        ->assertNotFound()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('error')
            ->where('status', 404)
            ->where('name', config('app.name')));
});

it('renders the statuses users meet as the branded error page', function (int $status): void {
    $this->get('_test/abort/'.$status)
        ->assertStatus($status)
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('error')
            ->where('status', $status));
})->with([403, 404, 429, 503]);

it('renders a forbidden admin page as the branded error page', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.dashboard.index'))
        ->assertForbidden()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('error')
            ->where('status', 403)
            ->where('auth.user.email', fn (string $email): bool => $email !== ''));
});

it('hides server errors behind the branded page unless debugging', function (): void {
    config()->set('app.debug', false);

    $this->get('_test/broken')
        ->assertInternalServerError()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('error')
            ->where('status', 500));
});

it("keeps Laravel's debug page for server errors while debugging", function (): void {
    config()->set('app.debug', true);

    $response = $this->get('_test/broken')->assertInternalServerError();

    expect($response->baseResponse->headers->get('X-Inertia'))->toBeNull()
        ->and((string) $response->getContent())->toContain('Boom.');
});

it('sends an expired page back with a toast instead of an error page', function (): void {
    $this->from('/settings/profile')
        ->post('_test/expired')
        ->assertRedirect('/settings/profile')
        ->assertInertiaFlash('toast', ['type' => 'error', 'message' => 'The page expired. Please try again.']);
});

it('answers JSON clients with JSON, not the error page', function (): void {
    $this->getJson('/this-page-does-not-exist')
        ->assertNotFound()
        ->assertJsonStructure(['message']);
});

it('answers Inertia visits with the error page component', function (): void {
    $version = $this->get('/this-page-does-not-exist')->viewData('page')['version'];

    $this->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => (string) $version])
        ->get('/this-page-does-not-exist')
        ->assertNotFound()
        ->assertJsonPath('component', 'error')
        ->assertJsonPath('props.status', 404);
});
