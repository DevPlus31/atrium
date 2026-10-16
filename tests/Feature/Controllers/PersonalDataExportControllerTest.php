<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Activitylog\Models\Activity;

it('sends guests to sign in', function (): void {
    $this->get(route('privacy.export'))->assertRedirectToRoute('login');
});

it('asks for the password first', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('privacy.export'))
        ->assertRedirectToRoute('password.confirm');
});

it('downloads everything held about the user as a JSON file', function (): void {
    $user = User::factory()->create(['name' => 'Ada', 'email' => 'ada@example.com']);

    $response = $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('privacy.export'))
        ->assertOk()
        ->assertHeader('Content-Disposition', sprintf('attachment; filename="personal-data-%s.json"', now()->toDateString()))
        ->assertJsonPath('account.name', 'Ada')
        ->assertJsonPath('account.email', 'ada@example.com')
        ->assertJsonMissingPath('account.password');

    expect($response->getContent())->toContain("\n    \"exported_at\"")
        ->and(Activity::query()->where('event', 'personal-data-exported')->sole()->causer_id)->toBe($user->id);
});

it('hands an Inertia visit over to the browser, which downloads the file', function (): void {
    $this->actingAs(User::factory()->create())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->withHeader('X-Inertia', 'true')
        ->get(route('privacy.export'))
        ->assertStatus(409)
        ->assertHeader('X-Inertia-Location', route('privacy.export'));

    expect(Activity::query()->where('event', 'personal-data-exported')->exists())->toBeFalse();
});

it('throttles downloads', function (): void {
    $this->actingAs(User::factory()->create())
        ->withSession(['auth.password_confirmed_at' => time()]);

    foreach (range(1, 6) as $attempt) {
        $this->get(route('privacy.export'))->assertOk();
    }

    $this->get(route('privacy.export'))->assertTooManyRequests();
});
