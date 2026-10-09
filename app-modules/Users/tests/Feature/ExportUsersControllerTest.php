<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('keeps guests and non-admins out', function (): void {
    assertAdminOnly('get', route('admin.users.export'));
});

it('forbids admins without the users.export permission', function (): void {
    $response = $this->actingAs(adminWithout('users.export'))->get(route('admin.users.export'));

    $response->assertForbidden();
});

it('streams a csv of all users', function (): void {
    $admin = adminUser();
    $user = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    $response = $this->actingAs($admin)->get(route('admin.users.export'));

    $response->assertOk()
        ->assertDownload('users.csv');

    $csv = $response->streamedContent();

    expect($csv)->toContain('id,name,email,email_verified_at,roles,created_at')
        ->toContain('jane@example.com')
        ->toContain($admin->email);
});

it('neutralises spreadsheet formulas in exported cells', function (string $name): void {
    $admin = adminUser();
    User::factory()->create(['name' => $name, 'email' => 'formula@example.com']);

    $csv = $this->actingAs($admin)->get(route('admin.users.export'))->streamedContent();

    $row = collect(explode("\n", (string) $csv))->first(fn (string $line): bool => str_contains($line, 'formula@example.com'));

    expect(str_getcsv((string) $row, escape: '')[1])->toBe("'".$name);
})->with([
    'equals' => '=HYPERLINK("http://evil.test","click")',
    'plus' => '+1+1',
    'minus' => '-2+3',
    'at' => '@SUM(1,1)',
]);

it('neutralises spreadsheet formulas in role names too', function (): void {
    $admin = adminUser();
    User::factory()->create(['email' => 'formula@example.com'])->assignRole(Role::findOrCreate('=EVIL()'));

    $csv = $this->actingAs($admin)->get(route('admin.users.export'))->streamedContent();

    $row = collect(explode("\n", (string) $csv))->first(fn (string $line): bool => str_contains($line, 'formula@example.com'));

    expect(str_getcsv((string) $row, escape: '')[4])->toBe("'=EVIL()");
});

it('exports only the currently filtered rows', function (): void {
    $admin = adminUser();
    User::factory()->create(['name' => 'Alice Wonder', 'email' => 'alice@example.com']);
    User::factory()->create(['name' => 'Bob Builder', 'email' => 'bob@example.com']);

    $response = $this->actingAs($admin)->get(route('admin.users.export', ['filter' => ['search' => 'alice']]));

    $csv = $response->streamedContent();

    expect($csv)->toContain('alice@example.com')
        ->not->toContain('bob@example.com')
        ->not->toContain($admin->email);
});

it('logs the export activity with the applied filters', function (): void {
    $admin = adminUser();

    $this->actingAs($admin)
        ->get(route('admin.users.export', ['filter' => ['verified' => 'yes']]))
        ->assertOk();

    $activity = Activity::query()->where('event', 'exported')->sole();

    expect($activity->causer_id)->toBe($admin->id)
        ->and($activity->getProperty('filter'))->toBe(['verified' => 'yes']);
});
