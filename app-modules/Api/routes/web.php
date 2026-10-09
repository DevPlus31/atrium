<?php

declare(strict_types=1);

use App\Modules\Middleware\EnsureModuleIsEnabled;
use Illuminate\Support\Facades\Route;
use Modules\Api\Http\Controllers\ApiTokenController;

// Every user manages their own tokens, from their account settings, after
// confirming their password (a hijacked session cannot quietly mint one).
Route::middleware(['auth', 'password.confirm', EnsureModuleIsEnabled::class.':api'])->group(function (): void {
    Route::get('settings/api-tokens', [ApiTokenController::class, 'index'])->name('api-tokens.index');
    Route::post('settings/api-tokens', [ApiTokenController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('api-tokens.store');
    Route::delete('settings/api-tokens/{token}', [ApiTokenController::class, 'destroy'])
        ->whereNumber('token')
        ->name('api-tokens.destroy');
});
