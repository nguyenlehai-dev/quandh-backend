<?php

use App\Modules\Meeting\Http\Controllers\Admin\MeetingAdminController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [MeetingAdminController::class, 'dashboard'])->middleware('permission:meetings.dashboard,web');
Route::get('/reports', [MeetingAdminController::class, 'reports'])->middleware('permission:meetings.dashboard,web');
Route::get('/all-documents', [MeetingAdminController::class, 'allDocuments'])->middleware('permission:meeting-documents.index,web');
Route::get('/all-conclusions', [MeetingAdminController::class, 'allConclusions'])->middleware('permission:meeting-conclusions.index,web');
Route::get('/all-votings', [MeetingAdminController::class, 'allVotings'])->middleware('permission:meeting-votings.index,web');
Route::get('/', [MeetingAdminController::class, 'index'])->middleware('permission:meetings.index,web');
Route::get('/{meeting}', [MeetingAdminController::class, 'show'])->middleware('permission:meetings.show,web');
Route::get('/{meeting}/live', [MeetingAdminController::class, 'live'])->middleware('permission:meetings.live-control,web');
Route::get('/{meeting}/participant-candidates', [MeetingAdminController::class, 'participantCandidates'])->middleware('permission:meeting-participants.index,web');
Route::get('/{meeting}/qr-token', [MeetingAdminController::class, 'qrToken'])->middleware('permission:meetings.show,web');
Route::post('/{meeting}/qr-checkin', [MeetingAdminController::class, 'qrCheckin'])->middleware('permission:meeting-participants.checkin,web');
