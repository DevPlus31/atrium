<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Audit\Http\Controllers\AuditController;

Route::get('audit', [AuditController::class, 'index'])->name('audit.index');
