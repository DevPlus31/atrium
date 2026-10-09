<?php

declare(strict_types=1);

use App\Actions\RemoveAvatar;
use App\Actions\UpdateAvatar;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    Storage::fake('public');
});

it('stores the photo and replaces the previous one', function (): void {
    $user = User::factory()->create();
    $action = resolve(UpdateAvatar::class);

    $action->handle($user, UploadedFile::fake()->image('one.png', 200, 200));
    $action->handle($user, UploadedFile::fake()->image('two.jpg', 200, 200));

    $media = $user->refresh()->getMedia(User::AVATAR);

    expect($media)->toHaveCount(1)
        ->and($media->first()?->file_name)->toBe('avatar.jpg')
        ->and(Activity::query()->where('event', 'avatar-updated')->count())->toBe(2);
});

it('removes the photo', function (): void {
    $user = User::factory()->create();
    resolve(UpdateAvatar::class)->handle($user, UploadedFile::fake()->image('me.png', 200, 200));

    resolve(RemoveAvatar::class)->handle($user);

    expect($user->refresh()->hasMedia(User::AVATAR))->toBeFalse()
        ->and(Activity::query()->where('event', 'avatar-removed')->exists())->toBeTrue();
});

it('does nothing when there is no photo to remove', function (): void {
    $user = User::factory()->create();

    resolve(RemoveAvatar::class)->handle($user);

    expect(Activity::query()->where('event', 'avatar-removed')->exists())->toBeFalse();
});
