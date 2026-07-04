<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('bare-module', fn (): array => ['module' => 'bare-module'])->name('bare-module.index');
