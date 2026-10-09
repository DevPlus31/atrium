<?php

declare(strict_types=1);

it('installs the Playwright version the browser tests use', function (): void {
    /** @var array{devDependencies: array<string, string>} $package */
    $package = json_decode((string) file_get_contents(base_path('package.json')), true, flags: JSON_THROW_ON_ERROR);

    preg_match('/^ARG PLAYWRIGHT_VERSION=(\S+)$/m', (string) file_get_contents(base_path('docker/dev/Dockerfile')), $matches);

    expect($matches[1] ?? null)->toBe(mb_ltrim($package['devDependencies']['playwright'], '^~'));
});
