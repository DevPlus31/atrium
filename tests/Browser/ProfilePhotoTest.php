<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Storage;

// Uploading itself is covered by tests/Feature/Controllers/UserAvatarControllerTest:
// Pest's in-process browser server mangles binary multipart bodies (it trims
// each part with mb_ltrim), so a browser-driven upload cannot pass validation.
it('shows the profile photo and removes it from the profile settings', function (): void {
    Storage::fake('public');
    $user = User::factory()->withoutTwoFactor()->create();
    $user->addMedia(base_path('tests/Fixtures/images/avatar.png'))
        ->preservingOriginal()
        ->toMediaCollection(User::AVATAR);
    $this->actingAs($user);

    visit(route('user-profile.edit'))
        ->assertSee('Change')
        ->assertPresent('img[src*="avatar"]')
        ->click('Remove')
        ->assertSee('Photo removed.')
        ->assertSee('Upload')
        ->assertNoJavaScriptErrors();

    expect($user->refresh()->hasMedia(User::AVATAR))->toBeFalse();
});
