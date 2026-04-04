<?php

use App\Modules\Meeting\Http\Controllers\Participant\MyMeetingController;
use Illuminate\Support\Facades\Route;

Route::get('/my-meetings', [MyMeetingController::class, 'index'])->middleware('permission:my-meetings.index,web');
Route::get('/meetings/{meeting}', [MyMeetingController::class, 'show'])->middleware('permission:my-meetings.show,web');
Route::get('/meetings/{meeting}/documents', [MyMeetingController::class, 'documents'])->middleware('permission:my-meetings.show,web');
Route::get('/meetings/{meeting}/personal-notes', [MyMeetingController::class, 'personalNotes'])->middleware('permission:my-meetings.note,web');
Route::post('/meetings/{meeting}/personal-notes', [MyMeetingController::class, 'storePersonalNote'])->middleware('permission:my-meetings.note,web');
Route::put('/meetings/{meeting}/personal-notes/{note}', [MyMeetingController::class, 'updatePersonalNote'])->middleware('permission:my-meetings.note,web');
Route::delete('/meetings/{meeting}/personal-notes/{note}', [MyMeetingController::class, 'destroyPersonalNote'])->middleware('permission:my-meetings.note,web');
Route::get('/meetings/{meeting}/conclusions', [MyMeetingController::class, 'conclusions'])->middleware('permission:my-meetings.show,web');
Route::get('/meetings/{meeting}/speech-requests/mine', [MyMeetingController::class, 'speechRequestsMine'])->middleware('permission:my-meetings.speech-request,web');
Route::post('/meetings/{meeting}/speech-requests', [MyMeetingController::class, 'storeSpeechRequest'])->middleware('permission:my-meetings.speech-request,web');
Route::get('/meetings/{meeting}/votings/current', [MyMeetingController::class, 'currentVoting'])->middleware('permission:my-meetings.vote,web');
Route::post('/meetings/{meeting}/votings/{voting}/vote', [MyMeetingController::class, 'vote'])->middleware('permission:my-meetings.vote,web');
Route::get('/meetings/{meeting}/votings/{voting}/result', [MyMeetingController::class, 'votingResult'])->middleware('permission:my-meetings.vote,web');
Route::post('/meetings/{meeting}/self-checkin', [MyMeetingController::class, 'selfCheckin'])->middleware('permission:my-meetings.checkin,web');
Route::post('/meetings/{meeting}/qr-checkin', [MyMeetingController::class, 'qrCheckin'])->middleware('permission:my-meetings.checkin,web');
