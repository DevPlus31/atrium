<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Audit\Policies\ActivityPolicy;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    Permission::findOrCreate('audit.view');

    $this->policy = new ActivityPolicy();
});

it('allows viewing the audit log only with the audit.view permission', function (): void {
    $user = User::factory()->create();

    expect($this->policy->viewAny($user))->toBeFalse();

    $user->givePermissionTo('audit.view');

    expect($this->policy->viewAny($user->refresh()))->toBeTrue();
});
