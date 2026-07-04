<?php

declare(strict_types=1);

use Spatie\Permission\Models\Role;

it('seeds the admin and super-admin roles', function (): void {
    $this->seed();

    expect(Role::query()->pluck('name')->all())->toBe(['admin', 'super-admin']);
});

it('is idempotent', function (): void {
    $this->seed();
    $this->seed();

    expect(Role::query()->count())->toBe(2);
});
