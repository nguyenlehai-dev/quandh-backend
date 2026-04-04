<?php

use App\Modules\Meeting\MeetingDocumentFieldController;
use Illuminate\Support\Facades\Route;

Route::get('/public', [MeetingDocumentFieldController::class, 'public']);
Route::get('/public-options', [MeetingDocumentFieldController::class, 'publicOptions']);
Route::get('/export', [MeetingDocumentFieldController::class, 'export'])->middleware('permission:meeting-document-fields.index,web');
Route::post('/import', [MeetingDocumentFieldController::class, 'import'])->middleware('permission:meeting-document-fields.store,web');
Route::post('/bulk-delete', [MeetingDocumentFieldController::class, 'bulkDestroy'])->middleware('permission:meeting-document-fields.destroy,web');
Route::patch('/bulk-status', [MeetingDocumentFieldController::class, 'bulkUpdateStatus'])->middleware('permission:meeting-document-fields.update,web');
Route::get('/stats', [MeetingDocumentFieldController::class, 'stats'])->middleware('permission:meeting-document-fields.index,web');
Route::get('/', [MeetingDocumentFieldController::class, 'index'])->middleware('permission:meeting-document-fields.index,web');
Route::get('/{meetingDocumentField}', [MeetingDocumentFieldController::class, 'show'])->middleware('permission:meeting-document-fields.show,web');
Route::post('/', [MeetingDocumentFieldController::class, 'store'])->middleware('permission:meeting-document-fields.store,web');
Route::put('/{meetingDocumentField}', [MeetingDocumentFieldController::class, 'update'])->middleware('permission:meeting-document-fields.update,web');
Route::patch('/{meetingDocumentField}', [MeetingDocumentFieldController::class, 'update'])->middleware('permission:meeting-document-fields.update,web');
Route::delete('/{meetingDocumentField}', [MeetingDocumentFieldController::class, 'destroy'])->middleware('permission:meeting-document-fields.destroy,web');
Route::patch('/{meetingDocumentField}/status', [MeetingDocumentFieldController::class, 'changeStatus'])->middleware('permission:meeting-document-fields.update,web');
