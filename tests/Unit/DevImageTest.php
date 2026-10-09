<?php

declare(strict_types=1);

it('installs the Playwright version the browser tests use', function (): void {
    /** @var array{devDependencies: array<string, string>} $package */
    $package = json_decode((string) file_get_contents(base_path('package.json')), true, flags: JSON_THROW_ON_ERROR);

    preg_match('/^ARG PLAYWRIGHT_VERSION=(\S+)$/m', (string) file_get_contents(base_path('docker/dev/Dockerfile')), $matches);

    expect($matches[1] ?? null)->toBe(mb_ltrim($package['devDependencies']['playwright'], '^~'));
});

it('copies the Bun version package.json declares into every image', function (string $dockerfile): void {
    /** @var array{packageManager: string} $package */
    $package = json_decode((string) file_get_contents(base_path('package.json')), true, flags: JSON_THROW_ON_ERROR);

    preg_match('/COPY --from=oven\/bun:(\S+) /', (string) file_get_contents(base_path($dockerfile)), $matches);

    expect('bun@'.($matches[1] ?? ''))->toBe($package['packageManager']);
})->with(['docker/dev/Dockerfile', 'docker/prod/Dockerfile']);

it('gives the dev image every PHP extension the production image has', function (): void {
    preg_match('/^ARG PHP_EXTENSIONS="([^"]+)"$/m', (string) file_get_contents(base_path('docker/prod/Dockerfile')), $production);
    preg_match('/install-php-extensions ([a-z_ ]+)/', (string) file_get_contents(base_path('docker/dev/Dockerfile')), $development);

    expect(explode(' ', $production[1] ?? ''))->each->toBeIn(explode(' ', mb_trim($development[1] ?? '')));
});
