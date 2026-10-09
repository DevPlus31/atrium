<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Audit\Widgets\AccountActivityWidget;

it('lists what others did to the account, newest first', function (): void {
    $user = User::factory()->create();
    $admin = User::factory()->create(['name' => 'Ada Admin']);

    activity('users')->performedOn($user)->causedBy($admin)->event('updated')->log('updated');
    $this->travel(1)->minutes();
    activity('users')->performedOn($user)->causedBy($admin)->event('impersonated')->log('impersonated');

    $entries = new AccountActivityWidget()($user)->entries;

    expect($entries)->toHaveCount(2)
        ->and($entries[0])->toMatchArray([
            'event' => 'impersonated',
            'causer' => 'Ada Admin',
            'created_at' => now()->toIso8601String(),
        ])
        ->and($entries[1]['event'])->toBe('updated');
});

it('ignores activity about other subjects and keeps the five latest', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();

    activity('users')->performedOn($other)->event('updated')->log('updated');

    foreach (range(1, 6) as $minute) {
        $this->travel(1)->minutes();
        activity('users')->performedOn($user)->event('updated')->log('updated');
    }

    $entries = new AccountActivityWidget()($user)->entries;

    expect($entries)->toHaveCount(5)
        ->and($entries[0]['causer'])->toBeNull()
        ->and($entries[0]['created_at'])->toBe(now()->toIso8601String());
});

it('leaves out what the member did to their own account', function (): void {
    $user = User::factory()->create();

    activity('users')->performedOn($user)->causedBy($user)->event('updated')->log('updated');

    expect(new AccountActivityWidget()($user)->entries)->toBe([]);
});
