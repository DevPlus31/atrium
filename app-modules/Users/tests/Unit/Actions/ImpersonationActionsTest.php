<?php

declare(strict_types=1);

use App\Models\User;
use Lab404\Impersonate\Services\ImpersonateManager;
use Modules\Users\Actions\ImpersonateUser;
use Spatie\Activitylog\Models\Activity;

it('impersonates the user and records who did it', function (): void {
    $admin = User::factory()->create();
    $target = User::factory()->create();
    $this->actingAs($admin);

    expect(resolve(ImpersonateUser::class)->handle($admin, $target))->toBeTrue();

    $activity = Activity::query()->where('event', 'impersonated')->sole();

    expect(auth()->id())->toBe($target->id)
        ->and($activity->causer_id)->toBe($admin->id)
        ->and($activity->subject_id)->toBe($target->id);
});

it('records nothing when impersonation is refused', function (): void {
    $admin = User::factory()->create();
    $target = User::factory()->create();
    $this->actingAs($admin);
    $this->partialMock(ImpersonateManager::class)->shouldReceive('take')->andReturnFalse();

    expect(resolve(ImpersonateUser::class)->handle($admin, $target))->toBeFalse()
        ->and(Activity::query()->where('event', 'impersonated')->exists())->toBeFalse();
});
