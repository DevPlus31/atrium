<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Users\Http\Controllers\AcceptInvitationController;

// The link in an invitation email: signed, for people without an account.
Route::middleware(['guest', 'signed'])->group(function (): void {
    Route::get('invitations/{invitation}', [AcceptInvitationController::class, 'show'])->name('invitations.show');
    Route::post('invitations/{invitation}', [AcceptInvitationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('invitations.store');
});
