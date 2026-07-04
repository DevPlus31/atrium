<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('test-module', fn (): array => ['module' => 'test-module'])->name('test-module.index');
