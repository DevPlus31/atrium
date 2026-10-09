<?php

declare(strict_types=1);

it('runs the dev queue listener without a wall-clock timeout', function (): void {
    /** @var array{scripts: array{dev: list<string>}} $composer */
    $composer = json_decode((string) file_get_contents(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);

    // queue:listen measures its child's timeout in wall-clock time, so a
    // laptop sleeping mid-poll trips it; with --kill-others that used to take
    // the whole `composer run dev` stack down.
    expect(implode(' ', $composer['scripts']['dev']))->toContain('queue:listen --tries=1 --timeout=0');
});
