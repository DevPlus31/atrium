<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Users\Actions\RecordUsersExport;
use Spatie\Activitylog\Models\Activity;

it('records the export with its filters', function (): void {
    $admin = User::factory()->create();
    $this->actingAs($admin);

    new RecordUsersExport()->handle(['verified' => 'yes']);

    $activity = Activity::query()->where('event', 'exported')->sole();

    expect($activity->log_name)->toBe('users')
        ->and($activity->causer_id)->toBe($admin->id)
        ->and($activity->getProperty('filter'))->toBe(['verified' => 'yes']);
});

it('logs only the filters the index understands, as bounded strings', function (): void {
    $this->actingAs(User::factory()->create());

    new RecordUsersExport()->handle([
        'search' => str_repeat('a', 300),
        'role' => ['nested' => 'array'],
        'injected' => 'anything',
    ]);

    expect(Activity::query()->where('event', 'exported')->sole()->getProperty('filter'))
        ->toBe(['search' => str_repeat('a', 255), 'role' => '']);
});
