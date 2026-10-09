<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\ModuleSwitch;
use Laravel\Pennant\Feature;

beforeEach(function (): void {
    Feature::define('module:alpha', fn (): bool => true);
});

it('is on when neither the config nor the flag switches it off', function (): void {
    expect(ModuleSwitch::isOn('alpha', User::factory()->create()))->toBeTrue();
});

it('is off for everyone when the module is disabled in config', function (): void {
    config()->set('modules.disabled', ['alpha']);

    expect(ModuleSwitch::isOn('alpha', User::factory()->create()))->toBeFalse();
});

it('is off for a user whose module flag is off', function (): void {
    $user = User::factory()->create();
    Feature::for($user)->deactivate('module:alpha');

    expect(ModuleSwitch::isOn('alpha', $user))->toBeFalse()
        ->and(ModuleSwitch::isOn('alpha', User::factory()->create()))->toBeTrue();
});
