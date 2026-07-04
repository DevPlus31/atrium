<?php

declare(strict_types=1);

use App\Modules\Data\NavItemData;

it('defaults the external flag to false', function (): void {
    $item = new NavItemData(
        label: 'Users',
        routeName: 'admin.users.index',
        href: 'https://example.com/admin/users',
        icon: 'users',
        group: 'Management',
        sort: 10,
    );

    expect($item->external)->toBeFalse();
});

it('serializes the external flag', function (): void {
    $item = new NavItemData(
        label: 'Pulse',
        routeName: 'pulse',
        href: 'https://example.com/pulse',
        icon: 'activity',
        group: 'System',
        sort: 90,
        external: true,
    );

    expect($item->toArray())->toBe([
        'label' => 'Pulse',
        'routeName' => 'pulse',
        'href' => 'https://example.com/pulse',
        'icon' => 'activity',
        'group' => 'System',
        'sort' => 90,
        'external' => true,
    ]);
});
