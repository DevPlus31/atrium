<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;
use Modules\Users\Http\Controllers\DeleteUsersController;
use Modules\Users\Http\Controllers\ExportUsersController;
use Modules\Users\Http\Controllers\ImpersonateUserController;
use Modules\Users\Http\Controllers\InvitationController;
use Modules\Users\Http\Controllers\ResendInvitationController;
use Modules\Users\Http\Controllers\UserController;

Route::get('users/export', ExportUsersController::class)->name('users.export');

Route::post('users/{user}/impersonate', ImpersonateUserController::class)->name('users.impersonate');

Route::get('users/invitations', [InvitationController::class, 'index'])->name('users.invitations.index');
Route::post('users/invitations', [InvitationController::class, 'store'])
    ->middleware([HandlePrecognitiveRequests::class, 'throttle:20,1'])
    ->name('users.invitations.store');
Route::post('users/invitations/{invitation}/resend', ResendInvitationController::class)
    ->middleware('throttle:6,1')
    ->name('users.invitations.resend');
Route::delete('users/invitations/{invitation}', [InvitationController::class, 'destroy'])->name('users.invitations.destroy');

Route::delete('users', DeleteUsersController::class)->name('users.bulk-destroy');

Route::resource('users', UserController::class)
    ->except(['show'])
    ->middlewareFor(['store', 'update'], HandlePrecognitiveRequests::class);
