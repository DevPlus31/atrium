<?php

declare(strict_types=1);

use App\Actions\LeaveImpersonation;
use App\Models\User;
use Lab404\Impersonate\Services\ImpersonateManager;
use Spatie\Activitylog\Models\Activity;

it('leaves the impersonation and records the real admin as causer', function (): void {
    $admin = User::factory()->create();
    $target = User::factory()->create();
    $this->actingAs($admin);
    resolve(ImpersonateManager::class)->take($admin, $target);

    resolve(LeaveImpersonation::class)->handle($target);

    $activity = Activity::query()->where('event', 'impersonation-left')->sole();

    expect(auth()->id())->toBe($admin->id)
        ->and(resolve(ImpersonateManager::class)->isImpersonating())->toBeFalse()
        ->and($activity->causer_id)->toBe($admin->id)
        ->and($activity->subject_id)->toBe($target->id);
});
