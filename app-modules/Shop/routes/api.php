<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Shop\Http\Controllers\Api\V1\OrderController;

Route::apiResource('orders', OrderController::class)->only(['index', 'show']);
