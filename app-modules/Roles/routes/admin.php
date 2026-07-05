<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;
use Modules\Roles\Http\Controllers\RoleController;

Route::resource('roles', RoleController::class)
    ->except(['show'])
    ->middlewareFor(['store', 'update'], HandlePrecognitiveRequests::class);
