<?php

use App\Modules\Document\DocumentTypeController;
use Illuminate\Support\Facades\Route;

Route::get('/export', [DocumentTypeController::class, 'export'])->middleware('permission:document-types.export,api');
Route::post('/import', [DocumentTypeController::class, 'import'])->middleware('permission:document-types.import,api');
Route::post('/bulk-delete', [DocumentTypeController::class, 'bulkDestroy'])->middleware('permission:document-types.bulkDestroy,api');
Route::patch('/bulk-status', [DocumentTypeController::class, 'bulkUpdateStatus'])->middleware('permission:document-types.bulkUpdateStatus,api');
Route::get('/stats', [DocumentTypeController::class, 'stats'])->middleware('permission:document-types.stats,api');
Route::get('/', [DocumentTypeController::class, 'index'])->middleware('permission:document-types.index,api');
Route::get('/{documentType}', [DocumentTypeController::class, 'show'])->middleware('permission:document-types.show,api');
Route::post('/', [DocumentTypeController::class, 'store'])->middleware('permission:document-types.store,api');
Route::put('/{documentType}', [DocumentTypeController::class, 'update'])->middleware('permission:document-types.update,api');
Route::patch('/{documentType}', [DocumentTypeController::class, 'update'])->middleware('permission:document-types.update,api');
Route::delete('/{documentType}', [DocumentTypeController::class, 'destroy'])->middleware('permission:document-types.destroy,api');
Route::patch('/{documentType}/status', [DocumentTypeController::class, 'changeStatus'])->middleware('permission:document-types.changeStatus,api');
