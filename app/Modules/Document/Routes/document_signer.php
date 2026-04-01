<?php

use App\Modules\Document\DocumentSignerController;
use Illuminate\Support\Facades\Route;

Route::get('/export', [DocumentSignerController::class, 'export'])->middleware('permission:document-signers.export,api');
Route::post('/import', [DocumentSignerController::class, 'import'])->middleware('permission:document-signers.import,api');
Route::post('/bulk-delete', [DocumentSignerController::class, 'bulkDestroy'])->middleware('permission:document-signers.bulkDestroy,api');
Route::patch('/bulk-status', [DocumentSignerController::class, 'bulkUpdateStatus'])->middleware('permission:document-signers.bulkUpdateStatus,api');
Route::get('/stats', [DocumentSignerController::class, 'stats'])->middleware('permission:document-signers.stats,api');
Route::get('/', [DocumentSignerController::class, 'index'])->middleware('permission:document-signers.index,api');
Route::get('/{documentSigner}', [DocumentSignerController::class, 'show'])->middleware('permission:document-signers.show,api');
Route::post('/', [DocumentSignerController::class, 'store'])->middleware('permission:document-signers.store,api');
Route::put('/{documentSigner}', [DocumentSignerController::class, 'update'])->middleware('permission:document-signers.update,api');
Route::patch('/{documentSigner}', [DocumentSignerController::class, 'update'])->middleware('permission:document-signers.update,api');
Route::delete('/{documentSigner}', [DocumentSignerController::class, 'destroy'])->middleware('permission:document-signers.destroy,api');
Route::patch('/{documentSigner}/status', [DocumentSignerController::class, 'changeStatus'])->middleware('permission:document-signers.changeStatus,api');
