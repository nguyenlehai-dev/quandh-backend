<?php

use App\Modules\Document\IssuingAgencyController;
use Illuminate\Support\Facades\Route;

Route::get('/export', [IssuingAgencyController::class, 'export'])->middleware('permission:issuing-agencies.export,api');
Route::post('/import', [IssuingAgencyController::class, 'import'])->middleware('permission:issuing-agencies.import,api');
Route::post('/bulk-delete', [IssuingAgencyController::class, 'bulkDestroy'])->middleware('permission:issuing-agencies.bulkDestroy,api');
Route::patch('/bulk-status', [IssuingAgencyController::class, 'bulkUpdateStatus'])->middleware('permission:issuing-agencies.bulkUpdateStatus,api');
Route::get('/stats', [IssuingAgencyController::class, 'stats'])->middleware('permission:issuing-agencies.stats,api');
Route::get('/', [IssuingAgencyController::class, 'index'])->middleware('permission:issuing-agencies.index,api');
Route::get('/{issuingAgency}', [IssuingAgencyController::class, 'show'])->middleware('permission:issuing-agencies.show,api');
Route::post('/', [IssuingAgencyController::class, 'store'])->middleware('permission:issuing-agencies.store,api');
Route::put('/{issuingAgency}', [IssuingAgencyController::class, 'update'])->middleware('permission:issuing-agencies.update,api');
Route::patch('/{issuingAgency}', [IssuingAgencyController::class, 'update'])->middleware('permission:issuing-agencies.update,api');
Route::delete('/{issuingAgency}', [IssuingAgencyController::class, 'destroy'])->middleware('permission:issuing-agencies.destroy,api');
Route::patch('/{issuingAgency}/status', [IssuingAgencyController::class, 'changeStatus'])->middleware('permission:issuing-agencies.changeStatus,api');
