<?php

declare(strict_types=1);

use Laravel\Pennant\Feature;

it('registers the settings module feature flag', function (): void {
    expect(Feature::active('module:settings'))->toBeTrue();
});
