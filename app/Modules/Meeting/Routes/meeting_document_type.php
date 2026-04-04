<?php

use App\Modules\Meeting\MeetingDocumentTypeController;
use Illuminate\Support\Facades\Route;

Route::get('/public', [MeetingDocumentTypeController::class, 'public']);
Route::get('/public-options', [MeetingDocumentTypeController::class, 'publicOptions']);
Route::get('/export', [MeetingDocumentTypeController::class, 'export'])->middleware('permission:meeting-document-types.index,web');
Route::post('/import', [MeetingDocumentTypeController::class, 'import'])->middleware('permission:meeting-document-types.store,web');
Route::post('/bulk-delete', [MeetingDocumentTypeController::class, 'bulkDestroy'])->middleware('permission:meeting-document-types.destroy,web');
Route::patch('/bulk-status', [MeetingDocumentTypeController::class, 'bulkUpdateStatus'])->middleware('permission:meeting-document-types.update,web');
Route::get('/stats', [MeetingDocumentTypeController::class, 'stats'])->middleware('permission:meeting-document-types.index,web');
Route::get('/', [MeetingDocumentTypeController::class, 'index'])->middleware('permission:meeting-document-types.index,web');
Route::get('/{meetingDocumentType}', [MeetingDocumentTypeController::class, 'show'])->middleware('permission:meeting-document-types.show,web');
Route::post('/', [MeetingDocumentTypeController::class, 'store'])->middleware('permission:meeting-document-types.store,web');
Route::put('/{meetingDocumentType}', [MeetingDocumentTypeController::class, 'update'])->middleware('permission:meeting-document-types.update,web');
Route::patch('/{meetingDocumentType}', [MeetingDocumentTypeController::class, 'update'])->middleware('permission:meeting-document-types.update,web');
Route::delete('/{meetingDocumentType}', [MeetingDocumentTypeController::class, 'destroy'])->middleware('permission:meeting-document-types.destroy,web');
Route::patch('/{meetingDocumentType}/status', [MeetingDocumentTypeController::class, 'changeStatus'])->middleware('permission:meeting-document-types.update,web');
