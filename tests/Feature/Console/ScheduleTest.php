<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;

it('schedules the maintenance the production scheduler runs', function (): void {
    $commands = collect(resolve(Schedule::class)->events())
        ->map(fn (Event $event): string => (string) preg_replace('/^.*artisan[\'"]?\s+/', '', (string) $event->command))
        ->all();

    expect($commands)->toContain('horizon:snapshot')
        ->toContain('activitylog:clean')
        ->toContain('queue:prune-failed --hours=168')
        ->toContain('sanctum:prune-expired --hours=24');
});
