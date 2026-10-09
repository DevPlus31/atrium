<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\AuditLog;
use Spatie\Activitylog\Models\Activity;

it('records the event as the description unless one is given', function (): void {
    $subject = User::factory()->create();
    $causer = User::factory()->create();

    AuditLog::record('users', 'updated', $subject, ['attributes' => ['name' => 'Ada']], $causer);
    AuditLog::record('settings', 'updated', description: 'logo-removed');

    [$first, $second] = Activity::query()->oldest('id')->get()->all();

    expect($first->log_name)->toBe('users')
        ->and($first->event)->toBe('updated')
        ->and($first->description)->toBe('updated')
        ->and($first->subject?->is($subject))->toBeTrue()
        ->and($first->causer?->is($causer))->toBeTrue()
        ->and($first->getProperty('attributes'))->toBe(['name' => 'Ada'])
        ->and($second->description)->toBe('logo-removed')
        ->and($second->subject_id)->toBeNull()
        ->and($second->properties->all())->toBe([]);
});
