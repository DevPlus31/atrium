<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;
use Modules\Settings\Http\Controllers\AnnouncementController;
use Modules\Settings\Http\Controllers\GeneralSettingsController;
use Modules\Settings\Http\Controllers\LogoController;

Route::get('settings/general', [GeneralSettingsController::class, 'edit'])->name('settings.general.edit');
Route::put('settings/general', [GeneralSettingsController::class, 'update'])
    ->middleware(HandlePrecognitiveRequests::class)
    ->name('settings.general.update');

// Uploads travel as multipart POST (PUT cannot carry files in PHP).
Route::post('settings/logo', [LogoController::class, 'update'])->name('settings.logo.update');
Route::delete('settings/logo', [LogoController::class, 'destroy'])->name('settings.logo.destroy');

Route::get('settings/announcement', [AnnouncementController::class, 'edit'])->name('settings.announcement.edit');
Route::put('settings/announcement', [AnnouncementController::class, 'update'])->name('settings.announcement.update');
Route::delete('settings/announcement', [AnnouncementController::class, 'destroy'])->name('settings.announcement.destroy');
