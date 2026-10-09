<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

it('stores subject ids as strings so uuid and integer keys both fit', function (): void {
    expect(Schema::getColumnType('activity_log', 'subject_id'))->toBe('varchar');
});

it('stores causer ids as uuids matching the users key', function (): void {
    // SQLite has no uuid type; PostgreSQL must get a real uuid column.
    expect(Schema::getColumnType('activity_log', 'causer_id'))->toBe(DB::getDriverName() === 'pgsql' ? 'uuid' : 'varchar');
});

it('logs activity about uuid and integer keyed models', function (): void {
    $user = User::factory()->create();
    $role = Role::findOrCreate('editor');

    activity()->causedBy($user)->performedOn($user)->log('uuid subject');
    activity()->causedBy($user)->performedOn($role)->log('integer subject');

    expect(Activity::query()->where('description', 'uuid subject')->sole()->subject_id)->toBe($user->id)
        ->and((string) Activity::query()->where('description', 'integer subject')->sole()->subject_id)->toBe((string) $role->id);
});
