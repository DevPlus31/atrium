<?php

declare(strict_types=1);

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('keeps one admin layout instance across visits and updates the breadcrumbs', function (): void {
    $this->actingAs(adminUser(['layout' => ['nav_placement' => 'sidebar-left']]));

    $page = visit(route('admin.dashboard.index'));

    $page->script("document.querySelector('[data-slot=\"sidebar\"]').dataset.persisted = 'yes'");

    $page->click('Roles')
        ->assertPathIs('/admin/roles')
        ->assertScript("document.querySelector('[data-slot=\"sidebar\"]').dataset.persisted", 'yes')
        ->assertScript("document.querySelector('header nav[aria-label=\"breadcrumb\"]')?.textContent.includes('Roles')", true)
        ->assertNoJavaScriptErrors();
});
