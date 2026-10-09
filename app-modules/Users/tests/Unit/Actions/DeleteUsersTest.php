<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Users\Actions\DeleteUsers;

it('deletes every given user', function (): void {
    $users = User::factory()->count(2)->create();
    $kept = User::factory()->create();

    resolve(DeleteUsers::class)->handle($users);

    expect(User::query()->pluck('id')->all())->toBe([$kept->id]);
});
