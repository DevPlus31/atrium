<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\ProductController;
use Modules\Catalog\Http\Controllers\PublishProductController;

Route::resource('products', ProductController::class)
    ->except(['show'])
    ->middlewareFor(['store', 'update'], HandlePrecognitiveRequests::class);

Route::post('products/{product}/publish', PublishProductController::class)
    ->name('products.publish');
