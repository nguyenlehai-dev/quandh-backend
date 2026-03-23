<?php

use App\Modules\Meeting\MeetingAgendaController;
use App\Modules\Meeting\MeetingConclusionController;
use App\Modules\Meeting\MeetingController;
use App\Modules\Meeting\MeetingDocumentController;
use App\Modules\Meeting\MeetingParticipantController;
use App\Modules\Meeting\MeetingPersonalNoteController;
use App\Modules\Meeting\MeetingSpeechRequestController;
use App\Modules\Meeting\MeetingVotingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Meeting Module Routes
|--------------------------------------------------------------------------
| Các route cho module Cuộc họp không giấy.
| Prefix: /api/meetings
*/

// === Meeting chính (11 hàm bắt buộc) ===
Route::get('/export', [MeetingController::class, 'export'])->middleware('permission:meetings.export,web');
Route::post('/import', [MeetingController::class, 'import'])->middleware('permission:meetings.import,web');
Route::post('/bulk-delete', [MeetingController::class, 'bulkDestroy'])->middleware('permission:meetings.bulkDestroy,web');
Route::patch('/bulk-status', [MeetingController::class, 'bulkUpdateStatus'])->middleware('permission:meetings.bulkUpdateStatus,web');
Route::get('/stats', [MeetingController::class, 'stats'])->middleware('permission:meetings.stats,web');
Route::get('/', [MeetingController::class, 'index'])->middleware('permission:meetings.index,web');
Route::get('/{meeting}', [MeetingController::class, 'show'])->middleware('permission:meetings.show,web');
Route::post('/', [MeetingController::class, 'store'])->middleware('permission:meetings.store,web');
Route::put('/{meeting}', [MeetingController::class, 'update'])->middleware('permission:meetings.update,web');
Route::patch('/{meeting}', [MeetingController::class, 'update'])->middleware('permission:meetings.update,web');
Route::delete('/{meeting}', [MeetingController::class, 'destroy'])->middleware('permission:meetings.destroy,web');
Route::patch('/{meeting}/status', [MeetingController::class, 'changeStatus'])->middleware('permission:meetings.changeStatus,web');

// === Participants (Thành viên) ===
Route::get('/{meeting}/participants', [MeetingParticipantController::class, 'index'])->middleware('permission:meeting-participants.index,web');
Route::post('/{meeting}/participants', [MeetingParticipantController::class, 'store'])->middleware('permission:meeting-participants.store,web');
Route::put('/{meeting}/participants/{participant}', [MeetingParticipantController::class, 'update'])->middleware('permission:meeting-participants.update,web');
Route::delete('/{meeting}/participants/{participant}', [MeetingParticipantController::class, 'destroy'])->middleware('permission:meeting-participants.destroy,web');
Route::patch('/{meeting}/participants/{participant}/checkin', [MeetingParticipantController::class, 'checkin'])->middleware('permission:meeting-participants.checkin,web');

// === Agendas (Chương trình nghị sự) ===
Route::get('/{meeting}/agendas', [MeetingAgendaController::class, 'index'])->middleware('permission:meeting-agendas.index,web');
Route::post('/{meeting}/agendas', [MeetingAgendaController::class, 'store'])->middleware('permission:meeting-agendas.store,web');
Route::put('/{meeting}/agendas/{agenda}', [MeetingAgendaController::class, 'update'])->middleware('permission:meeting-agendas.update,web');
Route::delete('/{meeting}/agendas/{agenda}', [MeetingAgendaController::class, 'destroy'])->middleware('permission:meeting-agendas.destroy,web');
Route::patch('/{meeting}/agendas/reorder', [MeetingAgendaController::class, 'reorder'])->middleware('permission:meeting-agendas.reorder,web');

// === Documents (Tài liệu) ===
Route::get('/{meeting}/documents', [MeetingDocumentController::class, 'index'])->middleware('permission:meeting-documents.index,web');
Route::post('/{meeting}/documents', [MeetingDocumentController::class, 'store'])->middleware('permission:meeting-documents.store,web');
Route::put('/{meeting}/documents/{document}', [MeetingDocumentController::class, 'update'])->middleware('permission:meeting-documents.update,web');
Route::delete('/{meeting}/documents/{document}', [MeetingDocumentController::class, 'destroy'])->middleware('permission:meeting-documents.destroy,web');

// === Conclusions (Kết luận) ===
Route::get('/{meeting}/conclusions', [MeetingConclusionController::class, 'index'])->middleware('permission:meeting-conclusions.index,web');
Route::post('/{meeting}/conclusions', [MeetingConclusionController::class, 'store'])->middleware('permission:meeting-conclusions.store,web');
Route::put('/{meeting}/conclusions/{conclusion}', [MeetingConclusionController::class, 'update'])->middleware('permission:meeting-conclusions.update,web');
Route::delete('/{meeting}/conclusions/{conclusion}', [MeetingConclusionController::class, 'destroy'])->middleware('permission:meeting-conclusions.destroy,web');

// === Personal Notes (Ghi chú cá nhân) ===
Route::get('/{meeting}/personal-notes', [MeetingPersonalNoteController::class, 'index'])->middleware('permission:meeting-personal-notes.index,web');
Route::post('/{meeting}/personal-notes', [MeetingPersonalNoteController::class, 'store'])->middleware('permission:meeting-personal-notes.store,web');
Route::put('/{meeting}/personal-notes/{note}', [MeetingPersonalNoteController::class, 'update'])->middleware('permission:meeting-personal-notes.update,web');
Route::delete('/{meeting}/personal-notes/{note}', [MeetingPersonalNoteController::class, 'destroy'])->middleware('permission:meeting-personal-notes.destroy,web');

// === Speech Requests (Đăng ký phát biểu) ===
Route::get('/{meeting}/speech-requests', [MeetingSpeechRequestController::class, 'index'])->middleware('permission:meeting-speech-requests.index,web');
Route::post('/{meeting}/speech-requests', [MeetingSpeechRequestController::class, 'store'])->middleware('permission:meeting-speech-requests.store,web');
Route::patch('/{meeting}/speech-requests/{speechRequest}/approve', [MeetingSpeechRequestController::class, 'approve'])->middleware('permission:meeting-speech-requests.approve,web');
Route::patch('/{meeting}/speech-requests/{speechRequest}/reject', [MeetingSpeechRequestController::class, 'reject'])->middleware('permission:meeting-speech-requests.reject,web');
Route::delete('/{meeting}/speech-requests/{speechRequest}', [MeetingSpeechRequestController::class, 'destroy'])->middleware('permission:meeting-speech-requests.destroy,web');

// === Votings (Biểu quyết) ===
Route::get('/{meeting}/votings', [MeetingVotingController::class, 'index'])->middleware('permission:meeting-votings.index,web');
Route::post('/{meeting}/votings', [MeetingVotingController::class, 'store'])->middleware('permission:meeting-votings.store,web');
Route::put('/{meeting}/votings/{voting}', [MeetingVotingController::class, 'update'])->middleware('permission:meeting-votings.update,web');
Route::delete('/{meeting}/votings/{voting}', [MeetingVotingController::class, 'destroy'])->middleware('permission:meeting-votings.destroy,web');
Route::patch('/{meeting}/votings/{voting}/open', [MeetingVotingController::class, 'open'])->middleware('permission:meeting-votings.open,web');
Route::patch('/{meeting}/votings/{voting}/close', [MeetingVotingController::class, 'close'])->middleware('permission:meeting-votings.close,web');
Route::post('/{meeting}/votings/{voting}/vote', [MeetingVotingController::class, 'vote'])->middleware('permission:meeting-votings.vote,web');
Route::get('/{meeting}/votings/{voting}/results', [MeetingVotingController::class, 'results'])->middleware('permission:meeting-votings.results,web');
