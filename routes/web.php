<?php

declare(strict_types=1);

use App\Http\Controllers\AccountController;
use App\Http\Controllers\DismissAnnouncementController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LeaveImpersonationController;
use App\Http\Controllers\MarkNotificationsReadController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\UserAvatarController;
use App\Http\Controllers\UserEmailResetNotificationController;
use App\Http\Controllers\UserEmailVerificationController;
use App\Http\Controllers\UserEmailVerificationNotificationController;
use App\Http\Controllers\UserNotificationController;
use App\Http\Controllers\UserNotificationPreferencesController;
use App\Http\Controllers\UserPasskeysController;
use App\Http\Controllers\UserPasswordController;
use App\Http\Controllers\UserPreferencesController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\UserSessionController;
use App\Http\Controllers\UserTwoFactorAuthenticationController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => to_route('dashboard'))->name('home');

// Anyone, signed in or not, may hide the site-wide announcement.
Route::post('announcement/dismiss', DismissAnnouncementController::class)
    ->middleware('throttle:30,1')
    ->name('announcement.dismiss');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('dashboard', LandingController::class)->name('dashboard');

    // Global Search (the admin command palette)...
    Route::get('search', SearchController::class)
        ->middleware(['can:'.User::PANEL_ABILITY, 'throttle:60,1'])
        ->name('search');

    // User Notifications...
    Route::get('notifications', [UserNotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications', MarkNotificationsReadController::class)->name('notifications.read-all');
    Route::patch('notifications/{notification}', [UserNotificationController::class, 'update'])
        ->whereUuid('notification')
        ->name('notifications.update');
});

Route::middleware('auth')->group(function (): void {
    // User...
    Route::delete('user', [AccountController::class, 'destroy'])->name('user.destroy');

    // Leaving an impersonation: open to the impersonated account, which
    // usually lacks the admin role.
    Route::post('impersonation/leave', LeaveImpersonationController::class)->name('impersonation.leave');

    // User Profile...
    Route::get('settings', fn () => to_route('user-profile.edit'));
    Route::get('settings/profile', [UserProfileController::class, 'edit'])->name('user-profile.edit');
    Route::patch('settings/profile', [UserProfileController::class, 'update'])->name('user-profile.update');

    // Uploads travel as multipart POST (PUT cannot carry files in PHP).
    Route::post('settings/avatar', [UserAvatarController::class, 'update'])
        ->middleware('throttle:10,1')
        ->name('user-avatar.update');
    Route::delete('settings/avatar', [UserAvatarController::class, 'destroy'])->name('user-avatar.destroy');

    // User Password...
    Route::get('settings/password', [UserPasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [UserPasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    // Appearance...
    Route::get('settings/appearance', fn () => Inertia::render('appearance/update'))->name('appearance.edit');

    // User Preferences (appearance / theme preset / layout)...
    Route::patch('settings/preferences', UserPreferencesController::class)->name('preferences.update');

    // User Sessions...
    Route::get('settings/sessions', [UserSessionController::class, 'index'])->name('sessions.index');
    Route::delete('settings/sessions', [UserSessionController::class, 'destroy'])
        ->middleware('throttle:6,1')
        ->name('sessions.destroy');

    // User Notification Preferences...
    Route::get('settings/notifications', [UserNotificationPreferencesController::class, 'edit'])->name('notification-preferences.edit');
    Route::put('settings/notifications', [UserNotificationPreferencesController::class, 'update'])->name('notification-preferences.update');

    // User Two-Factor Authentication...
    Route::get('settings/two-factor', [UserTwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');

    // User Passkeys...
    Route::get('settings/passkeys', [UserPasskeysController::class, 'show'])
        ->name('passkeys.show');
});

Route::middleware('guest')->group(function (): void {
    // User...
    Route::get('register', [RegistrationController::class, 'create'])
        ->name('register');
    Route::post('register', [RegistrationController::class, 'store'])
        ->middleware('throttle:5,60')
        ->name('register.store');

    // User Password...
    Route::get('reset-password/{token}', [UserPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('reset-password', [UserPasswordController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.store');

    // User Email Reset Notification...
    Route::get('forgot-password', [UserEmailResetNotificationController::class, 'create'])
        ->name('password.request');
    Route::post('forgot-password', [UserEmailResetNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');

    // Session...
    Route::get('login', [SessionController::class, 'create'])
        ->name('login');
    Route::post('login', [SessionController::class, 'store'])
        ->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    // User Email Verification...
    Route::get('verify-email', [UserEmailVerificationNotificationController::class, 'create'])
        ->name('verification.notice');
    Route::post('email/verification-notification', [UserEmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // User Email Verification...
    Route::get('verify-email/{id}/{hash}', [UserEmailVerificationController::class, 'update'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    // Session...
    Route::post('logout', [SessionController::class, 'destroy'])
        ->name('logout');
});
