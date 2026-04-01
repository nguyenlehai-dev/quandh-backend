<?php

use App\Modules\Document\DocumentFieldController;
use Illuminate\Support\Facades\Route;

Route::get('/export', [DocumentFieldController::class, 'export'])->middleware('permission:document-fields.export,api');
Route::post('/import', [DocumentFieldController::class, 'import'])->middleware('permission:document-fields.import,api');
Route::post('/bulk-delete', [DocumentFieldController::class, 'bulkDestroy'])->middleware('permission:document-fields.bulkDestroy,api');
Route::patch('/bulk-status', [DocumentFieldController::class, 'bulkUpdateStatus'])->middleware('permission:document-fields.bulkUpdateStatus,api');
Route::get('/stats', [DocumentFieldController::class, 'stats'])->middleware('permission:document-fields.stats,api');
Route::get('/', [DocumentFieldController::class, 'index'])->middleware('permission:document-fields.index,api');
Route::get('/{documentField}', [DocumentFieldController::class, 'show'])->middleware('permission:document-fields.show,api');
Route::post('/', [DocumentFieldController::class, 'store'])->middleware('permission:document-fields.store,api');
Route::put('/{documentField}', [DocumentFieldController::class, 'update'])->middleware('permission:document-fields.update,api');
Route::patch('/{documentField}', [DocumentFieldController::class, 'update'])->middleware('permission:document-fields.update,api');
Route::delete('/{documentField}', [DocumentFieldController::class, 'destroy'])->middleware('permission:document-fields.destroy,api');
Route::patch('/{documentField}/status', [DocumentFieldController::class, 'changeStatus'])->middleware('permission:document-fields.changeStatus,api');
