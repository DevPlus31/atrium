<?php

declare(strict_types=1);

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('ships the create ability following the admin permission', function (): void {
    assertPageAbilityFollowsPermission('admin.products.index', 'create', 'products.create');
});
