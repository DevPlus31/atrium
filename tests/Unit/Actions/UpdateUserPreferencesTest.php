<?php

declare(strict_types=1);

use App\Actions\UpdateUserPreferences;
use App\Enums\Appearance;
use App\Enums\ThemePreset;
use App\Models\User;

it('updates the appearance and theme preferences', function (): void {
    $user = User::factory()->create();

    $action = resolve(UpdateUserPreferences::class);

    $action->handle($user, ['appearance' => 'dark', 'theme' => 'ember']);

    $user->refresh();

    expect($user->appearance)->toBe(Appearance::Dark)
        ->and($user->theme)->toBe(ThemePreset::Ember);
});

it('updates the locale preference', function (): void {
    $user = User::factory()->create();

    $action = resolve(UpdateUserPreferences::class);

    $action->handle($user, ['locale' => 'en']);

    expect($user->refresh()->locale)->toBe('en');
});

it('merges layout updates over the stored layout', function (): void {
    $user = User::factory()->create([
        'layout' => ['direction' => 'rtl'],
    ]);

    $action = resolve(UpdateUserPreferences::class);

    $action->handle($user, ['layout' => ['content_width' => 'boxed']]);

    expect($user->refresh()->layout)->toBe([
        'direction' => 'rtl',
        'content_width' => 'boxed',
    ]);
});

it('ignores a non-array layout value', function (): void {
    $user = User::factory()->create([
        'layout' => ['direction' => 'rtl'],
    ]);

    $action = resolve(UpdateUserPreferences::class);

    $action->handle($user, ['layout' => 'boxed']);

    expect($user->refresh()->layout)->toBe(['direction' => 'rtl']);
});

it('does not touch the user when no preference keys are given', function (): void {
    $user = User::factory()->create();
    $updatedAt = $user->updated_at;

    $action = resolve(UpdateUserPreferences::class);

    $action->handle($user, []);

    $user->refresh();

    expect($user->appearance)->toBeNull()
        ->and($user->theme)->toBeNull()
        ->and($user->layout)->toBeNull()
        ->and($user->locale)->toBeNull()
        ->and($user->updated_at?->equalTo($updatedAt))->toBeTrue();
});
