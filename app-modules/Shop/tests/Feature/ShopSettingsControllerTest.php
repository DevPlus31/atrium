<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia;
use Modules\Shop\Settings\ShopSettings;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->withoutVite();
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('keeps the shop settings from members and admins without the permission', function (): void {
    $this->actingAs(User::factory()->create())->get(route('admin.shop.settings.edit'))->assertForbidden();

    Role::findByName('admin')->revokePermissionTo('shop.settings.update');

    $this->actingAs(adminUser())->get(route('admin.shop.settings.edit'))->assertForbidden();
    $this->actingAs(adminUser())->put(route('admin.shop.settings.update'), ['default_currency' => 'EUR'])->assertForbidden();
});

it('shows and saves the default currency', function (): void {
    $this->actingAs(adminUser())
        ->get(route('admin.shop.settings.edit'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('shop::settings')
            ->where('defaultCurrency', 'USD'));

    $this->actingAs(adminUser())
        ->put(route('admin.shop.settings.update'), ['default_currency' => ' eur '])
        ->assertRedirectToRoute('admin.shop.settings.edit')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Settings saved.']);

    expect(resolve(ShopSettings::class)->refresh()->default_currency)->toBe('EUR')
        ->and(Activity::query()->where('description', 'settings-updated')->sole()->getProperty('attributes'))
        ->toBe(['default_currency' => 'EUR']);
});

it('rejects a currency that is not an ISO code', function (): void {
    $this->actingAs(adminUser())
        ->put(route('admin.shop.settings.update'), ['default_currency' => 'EURO'])
        ->assertSessionHasErrors('default_currency');
});

it('starts new orders in the default currency', function (): void {
    $settings = resolve(ShopSettings::class);
    $settings->default_currency = 'GBP';
    $settings->save();

    $this->actingAs(adminUser())
        ->get(route('admin.orders.create'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('defaultCurrency', 'GBP'));
});

it('adds its settings page to the shared Settings menu group', function (): void {
    $this->actingAs(adminUser())
        ->get(route('admin.shop.settings.edit'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('nav', fn (Collection $nav): bool => collect($nav)->contains(fn (array $item): bool => $item['label'] === 'Shop' && $item['group'] === 'Settings')));
});

it('redirects guests to the login page', function (): void {
    $this->get(route('admin.shop.settings.edit'))->assertRedirectToRoute('login');
    $this->put(route('admin.shop.settings.update'))->assertRedirectToRoute('login');
});
