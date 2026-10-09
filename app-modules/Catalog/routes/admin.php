<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\DeleteProductsController;
use Modules\Catalog\Http\Controllers\ProductController;
use Modules\Catalog\Http\Controllers\PublishProductController;

Route::delete('products', DeleteProductsController::class)->name('products.bulk-destroy');

Route::resource('products', ProductController::class)
    ->except(['show'])
    ->middlewareFor(['store', 'update'], HandlePrecognitiveRequests::class);

Route::post('products/{product}/publish', PublishProductController::class)
    ->name('products.publish');
