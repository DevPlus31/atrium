<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;
use Modules\Shop\Http\Controllers\DeleteOrdersController;
use Modules\Shop\Http\Controllers\OrderController;
use Modules\Shop\Http\Controllers\ShopSettingsController;
use Modules\Shop\Http\Controllers\TransitionOrderController;

Route::delete('orders', DeleteOrdersController::class)->name('orders.bulk-destroy');

Route::resource('orders', OrderController::class)
    ->except(['show'])
    ->middlewareFor(['store', 'update'], HandlePrecognitiveRequests::class);

Route::post('orders/{order}/status', TransitionOrderController::class)
    ->name('orders.transition');

Route::get('settings/shop', [ShopSettingsController::class, 'edit'])->name('shop.settings.edit');
Route::put('settings/shop', [ShopSettingsController::class, 'update'])
    ->middleware(HandlePrecognitiveRequests::class)
    ->name('shop.settings.update');
