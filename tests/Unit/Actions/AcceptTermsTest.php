<?php

declare(strict_types=1);

use App\Actions\AcceptTerms;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

it('records the accepted version and when, on the account and in the audit log', function (): void {
    config(['privacy.terms_version' => '2030-01-01']);
    $user = User::factory()->withoutAcceptedTerms()->create();

    resolve(AcceptTerms::class)($user);

    $activity = Activity::query()->where('event', 'terms-accepted')->sole();

    expect($user->refresh()->terms_version)->toBe('2030-01-01')
        ->and($user->terms_accepted_at?->toIso8601String())->toBe(now()->toIso8601String())
        ->and($user->hasAcceptedCurrentTerms())->toBeTrue()
        ->and($activity->log_name)->toBe('privacy')
        ->and($activity->causer_id)->toBe($user->id)
        ->and($activity->getProperty('attributes'))->toBe(['version' => '2030-01-01']);
});

it('does nothing while acceptance is switched off', function (): void {
    config(['privacy.terms_version' => null]);
    $user = User::factory()->withoutAcceptedTerms()->create();

    resolve(AcceptTerms::class)($user);

    expect($user->refresh()->terms_version)->toBeNull()
        ->and(Activity::query()->where('event', 'terms-accepted')->exists())->toBeFalse();
});
