<?php

use App\Modules\Meeting\Controllers\MeetingChildController;
use App\Modules\Meeting\Controllers\MeetingController;
use Illuminate\Support\Facades\Route;

Route::get('/export', [MeetingController::class, 'export'])->middleware('permission:meetings.export,web');
Route::post('/import', [MeetingController::class, 'import'])->middleware('permission:meetings.import,web');
Route::post('/bulk-delete', [MeetingController::class, 'bulkDestroy'])->middleware('permission:meetings.bulkDestroy,web');
Route::patch('/bulk-status', [MeetingController::class, 'bulkUpdateStatus'])->middleware('permission:meetings.bulkUpdateStatus,web');
Route::get('/stats', [MeetingController::class, 'stats'])->middleware('permission:meetings.stats,web');
Route::get('/my-calendar', [MeetingController::class, 'myCalendar'])->middleware('permission:meetings.index,web');
Route::post('/check-in', [MeetingController::class, 'checkIn'])->middleware('permission:meetings.update,web');
Route::get('/', [MeetingController::class, 'index'])->middleware('permission:meetings.index,web');
Route::post('/', [MeetingController::class, 'store'])->middleware('permission:meetings.store,web');
Route::get('/{meeting}', [MeetingController::class, 'show'])->middleware('permission:meetings.show,web')->whereNumber('meeting');
Route::put('/{meeting}', [MeetingController::class, 'update'])->middleware('permission:meetings.update,web')->whereNumber('meeting');
Route::patch('/{meeting}', [MeetingController::class, 'update'])->middleware('permission:meetings.update,web')->whereNumber('meeting');
Route::delete('/{meeting}', [MeetingController::class, 'destroy'])->middleware('permission:meetings.destroy,web')->whereNumber('meeting');
Route::patch('/{meeting}/status', [MeetingController::class, 'changeStatus'])->middleware('permission:meetings.changeStatus,web')->whereNumber('meeting');
Route::get('/{meeting}/qr-token', [MeetingController::class, 'qrToken'])->middleware('permission:meetings.show,web')->whereNumber('meeting');
Route::post('/{meeting}/qr-token/regenerate', [MeetingController::class, 'regenerateQrToken'])->middleware('permission:meetings.update,web')->whereNumber('meeting');

foreach (['participants', 'agendas', 'documents', 'conclusions', 'speech-requests', 'votings', 'personal-notes', 'reminders'] as $child) {
    Route::get('/{meeting}/'.$child, [MeetingChildController::class, 'index'])->middleware('permission:meetings.show,web')->defaults('child', $child)->whereNumber('meeting');
    Route::post('/{meeting}/'.$child, [MeetingChildController::class, 'store'])->middleware('permission:meetings.update,web')->defaults('child', $child)->whereNumber('meeting');
    Route::put('/{meeting}/'.$child.'/{id}', [MeetingChildController::class, 'update'])->middleware('permission:meetings.update,web')->defaults('child', $child)->whereNumber('meeting')->whereNumber('id');
    Route::patch('/{meeting}/'.$child.'/{id}', [MeetingChildController::class, 'update'])->middleware('permission:meetings.update,web')->defaults('child', $child)->whereNumber('meeting')->whereNumber('id');
    Route::delete('/{meeting}/'.$child.'/{id}', [MeetingChildController::class, 'destroy'])->middleware('permission:meetings.update,web')->defaults('child', $child)->whereNumber('meeting')->whereNumber('id');
}

Route::post('/{meeting}/votings/{voting}/results', [MeetingChildController::class, 'storeVoteResult'])
    ->middleware('permission:meetings.update,web')
    ->whereNumber('meeting')
    ->whereNumber('voting');
