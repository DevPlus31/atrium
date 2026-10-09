<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Dashboard\Http\Controllers\MemberDashboardController;

// The member area: every verified account, no panel role required.
Route::middleware(['auth', 'verified'])
    ->get('home', MemberDashboardController::class)
    ->name('member.dashboard');
