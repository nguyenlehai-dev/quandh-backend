<?php

use App\Modules\Document\IssuingLevelController;
use Illuminate\Support\Facades\Route;

Route::get('/export', [IssuingLevelController::class, 'export'])->middleware('permission:issuing-levels.export,api');
Route::post('/import', [IssuingLevelController::class, 'import'])->middleware('permission:issuing-levels.import,api');
Route::post('/bulk-delete', [IssuingLevelController::class, 'bulkDestroy'])->middleware('permission:issuing-levels.bulkDestroy,api');
Route::patch('/bulk-status', [IssuingLevelController::class, 'bulkUpdateStatus'])->middleware('permission:issuing-levels.bulkUpdateStatus,api');
Route::get('/stats', [IssuingLevelController::class, 'stats'])->middleware('permission:issuing-levels.stats,api');
Route::get('/', [IssuingLevelController::class, 'index'])->middleware('permission:issuing-levels.index,api');
Route::get('/{issuingLevel}', [IssuingLevelController::class, 'show'])->middleware('permission:issuing-levels.show,api');
Route::post('/', [IssuingLevelController::class, 'store'])->middleware('permission:issuing-levels.store,api');
Route::put('/{issuingLevel}', [IssuingLevelController::class, 'update'])->middleware('permission:issuing-levels.update,api');
Route::patch('/{issuingLevel}', [IssuingLevelController::class, 'update'])->middleware('permission:issuing-levels.update,api');
Route::delete('/{issuingLevel}', [IssuingLevelController::class, 'destroy'])->middleware('permission:issuing-levels.destroy,api');
Route::patch('/{issuingLevel}/status', [IssuingLevelController::class, 'changeStatus'])->middleware('permission:issuing-levels.changeStatus,api');
