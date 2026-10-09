<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Api\Http\Controllers\Api\V1\CurrentUserController;

Route::get('user', CurrentUserController::class)->name('user');
