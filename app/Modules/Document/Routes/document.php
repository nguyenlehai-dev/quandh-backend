<?php

use App\Modules\Document\DocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/export', [DocumentController::class, 'export'])->middleware('permission:documents.export,api');
Route::post('/import', [DocumentController::class, 'import'])->middleware('permission:documents.import,api');
Route::post('/bulk-delete', [DocumentController::class, 'bulkDestroy'])->middleware('permission:documents.bulkDestroy,api');
Route::patch('/bulk-status', [DocumentController::class, 'bulkUpdateStatus'])->middleware('permission:documents.bulkUpdateStatus,api');
Route::get('/stats', [DocumentController::class, 'stats'])->middleware('permission:documents.stats,api');
Route::get('/', [DocumentController::class, 'index'])->middleware('permission:documents.index,api');
Route::get('/{document}', [DocumentController::class, 'show'])->middleware('permission:documents.show,api');
Route::post('/', [DocumentController::class, 'store'])->middleware('permission:documents.store,api');
Route::put('/{document}', [DocumentController::class, 'update'])->middleware('permission:documents.update,api');
Route::patch('/{document}', [DocumentController::class, 'update'])->middleware('permission:documents.update,api');
Route::delete('/{document}', [DocumentController::class, 'destroy'])->middleware('permission:documents.destroy,api');
Route::patch('/{document}/status', [DocumentController::class, 'changeStatus'])->middleware('permission:documents.changeStatus,api');
