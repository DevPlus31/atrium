<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Dashboard\Widgets\AccountOverviewWidget;

it('reports a secured account', function (): void {
    $user = User::factory()->create(['created_at' => now()->subYear()]);
    $user->passkeys()->create([
        'name' => 'Work laptop',
        'credential_id' => 'credential-1',
        'credential' => ['aaguid' => 'ea9b8d66-4d01-1d21-3ce4-b6b48cb575d4'],
    ]);

    expect(new AccountOverviewWidget()($user)->toArray())->toBe([
        'email_verified' => true,
        'two_factor_enabled' => true,
        'passkeys' => 1,
        'member_since' => now()->subYear()->toIso8601String(),
    ]);
});

it('reports what is still missing', function (): void {
    $user = User::factory()->unverified()->withoutTwoFactor()->create();

    $data = new AccountOverviewWidget()($user);

    expect($data->email_verified)->toBeFalse()
        ->and($data->two_factor_enabled)->toBeFalse()
        ->and($data->passkeys)->toBe(0);
});
