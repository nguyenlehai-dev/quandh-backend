<?php

use App\Modules\Core\LogActivityController;
use Illuminate\Support\Facades\Route;

Route::get('/export', [LogActivityController::class, 'export'])->name('log-activities.export')->middleware('permission:log-activities.export,web');
Route::get('/stats', [LogActivityController::class, 'stats'])->name('log-activities.stats')->middleware('permission:log-activities.stats,web');
Route::post('/delete-by-date', [LogActivityController::class, 'destroyByDate'])->name('log-activities.destroyByDate')->middleware('permission:log-activities.destroyByDate,web');
Route::post('/clear', [LogActivityController::class, 'destroyAll'])->name('log-activities.destroyAll')->middleware('permission:log-activities.destroyAll,web');
Route::post('/bulk-delete', [LogActivityController::class, 'bulkDestroy'])->name('log-activities.bulkDestroy')->middleware('permission:log-activities.bulkDestroy,web');
Route::get('/', [LogActivityController::class, 'index'])->name('log-activities.index')->middleware('permission:log-activities.index,web');
Route::get('/{logActivity}', [LogActivityController::class, 'show'])->name('log-activities.show')->middleware('permission:log-activities.show,web');
Route::delete('/{logActivity}', [LogActivityController::class, 'destroy'])->name('log-activities.destroy')->middleware('permission:log-activities.destroy,web');
