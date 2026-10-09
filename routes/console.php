<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

// Run by the `scheduler` service in production (php artisan schedule:work).

// Queue dashboard: Horizon's metrics come from these snapshots.
Schedule::command('horizon:snapshot')->everyFiveMinutes();

// Retention: the audit log keeps config('activitylog.clean_after_days') days,
// failed jobs a week, expired API tokens a day.
Schedule::command('activitylog:clean')->daily()->onOneServer();
Schedule::command('queue:prune-failed', ['--hours' => 168])->daily()->onOneServer();
Schedule::command('sanctum:prune-expired', ['--hours' => 24])->daily()->onOneServer();
