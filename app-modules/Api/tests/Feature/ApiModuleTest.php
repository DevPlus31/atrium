<?php

declare(strict_types=1);

use Laravel\Pennant\Feature;

it('registers the api module feature flag', function (): void {
    expect(Feature::active('module:api'))->toBeTrue();
});
