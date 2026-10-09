<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    Storage::fake('public');
    $this->withoutVite();
});

it('requires a signed-in user', function (): void {
    $this->post(route('user-avatar.update'))->assertRedirectToRoute('login');
    $this->delete(route('user-avatar.destroy'))->assertRedirectToRoute('login');
});

it('stores the photo with a square thumbnail and shares it', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('user-avatar.update'), ['avatar' => UploadedFile::fake()->image('me.jpg', 600, 400)])
        ->assertRedirectToRoute('user-profile.edit')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Photo updated.']);

    $media = $user->refresh()->getFirstMedia(User::AVATAR);

    expect($media)->not->toBeNull()
        ->and($media?->hasGeneratedConversion('thumb'))->toBeTrue()
        ->and(Storage::disk('public')->exists($media?->getPathRelativeToRoot('thumb') ?? ''))->toBeTrue()
        ->and(Activity::query()->where('event', 'avatar-updated')->sole()->subject_id)->toBe($user->id);

    $this->get(route('user-profile.edit'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('auth.user.avatar', $user->avatarUrl()));
});

it('replaces the previous photo rather than keeping both', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('user-avatar.update'), ['avatar' => UploadedFile::fake()->image('one.png', 200, 200)]);
    $this->actingAs($user)->post(route('user-avatar.update'), ['avatar' => UploadedFile::fake()->image('two.png', 200, 200)]);

    expect($user->refresh()->getMedia(User::AVATAR))->toHaveCount(1);
});

it('rejects files that are not a usable photo', function (UploadedFile $file): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('user-avatar.update'), ['avatar' => $file])
        ->assertSessionHasErrors('avatar');

    expect($user->refresh()->hasMedia(User::AVATAR))->toBeFalse();
})->with([
    'not an image' => fn (): UploadedFile => UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf'),
    'too small' => fn (): UploadedFile => UploadedFile::fake()->image('tiny.png', 32, 32),
    'too large' => fn (): UploadedFile => UploadedFile::fake()->image('huge.jpg', 200, 200)->size(5000),
    'unsupported type' => fn (): UploadedFile => UploadedFile::fake()->image('anim.gif', 200, 200),
]);

it('removes the photo and its files', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->post(route('user-avatar.update'), ['avatar' => UploadedFile::fake()->image('me.jpg', 200, 200)]);
    $path = $user->refresh()->getFirstMedia(User::AVATAR)?->getPathRelativeToRoot();

    $this->actingAs($user)
        ->delete(route('user-avatar.destroy'))
        ->assertRedirectToRoute('user-profile.edit')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Photo removed.']);

    expect($user->refresh()->hasMedia(User::AVATAR))->toBeFalse()
        ->and(Storage::disk('public')->exists((string) $path))->toBeFalse()
        ->and($user->avatarUrl())->toBeNull()
        ->and(Activity::query()->where('event', 'avatar-removed')->exists())->toBeTrue();
});

it('records nothing when there is no photo to remove', function (): void {
    $this->actingAs(User::factory()->create())->delete(route('user-avatar.destroy'));

    expect(Activity::query()->where('event', 'avatar-removed')->exists())->toBeFalse();
});

it('deletes the photo files with the account', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->post(route('user-avatar.update'), ['avatar' => UploadedFile::fake()->image('me.jpg', 200, 200)]);
    $path = $user->refresh()->getFirstMedia(User::AVATAR)?->getPathRelativeToRoot();

    $user->delete();

    expect(Storage::disk('public')->exists((string) $path))->toBeFalse();
});
