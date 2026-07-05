<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;
use Modules\Users\Http\Controllers\ExportUsersController;
use Modules\Users\Http\Controllers\ImpersonateUserController;
use Modules\Users\Http\Controllers\UserController;

Route::get('users/export', ExportUsersController::class)->name('users.export');

Route::post('users/{user}/impersonate', ImpersonateUserController::class)->name('users.impersonate');

Route::resource('users', UserController::class)
    ->except(['show'])
    ->middlewareFor(['store', 'update'], HandlePrecognitiveRequests::class);
