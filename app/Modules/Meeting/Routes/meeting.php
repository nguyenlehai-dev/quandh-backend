<?php

use App\Modules\Meeting\MeetingAgendaController;
use App\Modules\Meeting\MeetingConclusionController;
use App\Modules\Meeting\MeetingController;
use App\Modules\Meeting\MeetingDocumentController;
use App\Modules\Meeting\MeetingParticipantController;
use App\Modules\Meeting\MeetingPersonalNoteController;
use App\Modules\Meeting\MeetingSpeechRequestController;
use App\Modules\Meeting\MeetingVotingController;
use App\Modules\Meeting\AttendeeGroupController;
use App\Modules\Meeting\MeetingTypeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Meeting Module Routes
|--------------------------------------------------------------------------
| Các route cho module Cuộc họp không giấy.
| Prefix: /api/meetings
*/

// === Categories (Danh mục) ===
Route::get('/attendee-groups/export', [AttendeeGroupController::class, 'export'])->middleware('permission:attendee-groups.export,api');
Route::get('/attendee-groups', [AttendeeGroupController::class, 'index'])->middleware('permission:attendee-groups.index,api');
Route::post('/attendee-groups', [AttendeeGroupController::class, 'store'])->middleware('permission:attendee-groups.store,api');
Route::put('/attendee-groups/{attendee_group}', [AttendeeGroupController::class, 'update'])->middleware('permission:attendee-groups.update,api');
Route::delete('/attendee-groups/{attendee_group}', [AttendeeGroupController::class, 'destroy'])->middleware('permission:attendee-groups.destroy,api');
Route::post('/attendee-groups/{attendee_group}/members', [AttendeeGroupController::class, 'addMember'])->middleware('permission:attendee-groups.update,api');
Route::delete('/attendee-groups/{attendee_group}/members/{userId}', [AttendeeGroupController::class, 'removeMember'])->middleware('permission:attendee-groups.update,api');

Route::post('/meeting-types/bulk-delete', [MeetingTypeController::class, 'bulkDestroy'])->middleware('permission:meeting-types.destroy,api');
Route::put('/meeting-types/bulk-update', [MeetingTypeController::class, 'bulkUpdate'])->middleware('permission:meeting-types.update,api');
Route::get('/meeting-types/export', [MeetingTypeController::class, 'export'])->middleware('permission:meeting-types.export,api');
Route::get('/meeting-types', [MeetingTypeController::class, 'index'])->middleware('permission:meeting-types.index,api');
Route::post('/meeting-types', [MeetingTypeController::class, 'store'])->middleware('permission:meeting-types.store,api');
Route::put('/meeting-types/{meeting_type}', [MeetingTypeController::class, 'update'])->middleware('permission:meeting-types.update,api');
Route::delete('/meeting-types/{meeting_type}', [MeetingTypeController::class, 'destroy'])->middleware('permission:meeting-types.destroy,api');

// === Meeting chính (11 hàm bắt buộc) ===
Route::get('/export', [MeetingController::class, 'export'])->middleware('permission:meetings.export,api');
Route::post('/import', [MeetingController::class, 'import'])->middleware('permission:meetings.import,api');
Route::post('/bulk-delete', [MeetingController::class, 'bulkDestroy'])->middleware('permission:meetings.bulkDestroy,api');
Route::patch('/bulk-status', [MeetingController::class, 'bulkUpdateStatus'])->middleware('permission:meetings.bulkUpdateStatus,api');
Route::get('/stats', [MeetingController::class, 'stats'])->middleware('permission:meetings.stats,api');
Route::get('/', [MeetingController::class, 'index'])->middleware('permission:meetings.index,api');
Route::get('/all-votings/export', [MeetingVotingController::class, 'export'])->middleware('permission:meeting-votings.export,api');
Route::get('/all-votings', [MeetingVotingController::class, 'globalIndex'])->middleware('permission:meeting-votings.index,api');

Route::get('/all-conclusions/export', [MeetingConclusionController::class, 'export'])->middleware('permission:meeting-conclusions.export,api');
Route::get('/all-conclusions', [MeetingConclusionController::class, 'globalIndex'])->middleware('permission:meeting-conclusions.index,api');

Route::get('/all-documents/export', [MeetingDocumentController::class, 'export'])->middleware('permission:meeting-documents.export,api');
Route::get('/all-documents', [MeetingDocumentController::class, 'globalIndex'])->middleware('permission:meeting-documents.index,api');

Route::get('/all-participants/export', [MeetingParticipantController::class, 'export'])->middleware('permission:meeting-participants.export,api');
Route::get('/all-participants', [MeetingParticipantController::class, 'globalIndex'])->middleware('permission:meeting-participants.index,api');

Route::get('/my-calendar', [MeetingController::class, 'myCalendar'])->middleware('api');
Route::get('/{meeting}', [MeetingController::class, 'show'])->middleware('permission:meetings.show,api');
Route::post('/', [MeetingController::class, 'store'])->middleware('permission:meetings.store,api');
Route::put('/{meeting}', [MeetingController::class, 'update'])->middleware('permission:meetings.update,api');
Route::patch('/{meeting}', [MeetingController::class, 'update'])->middleware('permission:meetings.update,api');
Route::delete('/{meeting}', [MeetingController::class, 'destroy'])->middleware('permission:meetings.destroy,api');
Route::patch('/{meeting}/status', [MeetingController::class, 'changeStatus'])->middleware('permission:meetings.changeStatus,api');
Route::get('/{meeting}/qr-token', [MeetingController::class, 'qrToken'])->middleware('permission:meetings.show,api');
Route::post('/{meeting}/qr-checkin', [MeetingController::class, 'qrCheckin'])->middleware('permission:meetings.show,api');

// === Participants (Thành viên) ===
Route::get('/{meeting}/participants', [MeetingParticipantController::class, 'index'])->middleware('permission:meeting-participants.index,api');
Route::post('/{meeting}/participants', [MeetingParticipantController::class, 'store'])->middleware('permission:meeting-participants.store,api');
Route::put('/{meeting}/participants/{participant}', [MeetingParticipantController::class, 'update'])->middleware('permission:meeting-participants.update,api');
Route::delete('/{meeting}/participants/{participant}', [MeetingParticipantController::class, 'destroy'])->middleware('permission:meeting-participants.destroy,api');
Route::patch('/{meeting}/participants/{participant}/checkin', [MeetingParticipantController::class, 'checkin'])->middleware('permission:meeting-participants.checkin,api');
Route::post('/{meeting}/self-checkin', [MeetingParticipantController::class, 'selfCheckin'])->middleware('permission:meetings.show,api');
Route::get('/{meeting}/available-delegates', [MeetingParticipantController::class, 'availableDelegates'])->middleware('permission:meetings.show,api');

// === Agendas (Chương trình nghị sự) ===
Route::get('/{meeting}/agendas', [MeetingAgendaController::class, 'index'])->middleware('permission:meeting-agendas.index,api');
Route::post('/{meeting}/agendas', [MeetingAgendaController::class, 'store'])->middleware('permission:meeting-agendas.store,api');
Route::put('/{meeting}/agendas/{agenda}', [MeetingAgendaController::class, 'update'])->middleware('permission:meeting-agendas.update,api');
Route::delete('/{meeting}/agendas/{agenda}', [MeetingAgendaController::class, 'destroy'])->middleware('permission:meeting-agendas.destroy,api');
Route::patch('/{meeting}/agendas/reorder', [MeetingAgendaController::class, 'reorder'])->middleware('permission:meeting-agendas.reorder,api');
Route::patch('/{meeting}/agendas/{agenda}/set-active', [MeetingAgendaController::class, 'setActive'])->middleware('permission:meeting-agendas.setActive,api');

// === Documents (Tài liệu) ===
Route::get('/{meeting}/documents', [MeetingDocumentController::class, 'index'])->middleware('permission:meeting-documents.index,api');
Route::post('/{meeting}/documents', [MeetingDocumentController::class, 'store'])->middleware('permission:meeting-documents.store,api');
Route::put('/{meeting}/documents/{document}', [MeetingDocumentController::class, 'update'])->middleware('permission:meeting-documents.update,api');
Route::delete('/{meeting}/documents/{document}', [MeetingDocumentController::class, 'destroy'])->middleware('permission:meeting-documents.destroy,api');

// === Conclusions (Kết luận) ===
Route::get('/{meeting}/conclusions', [MeetingConclusionController::class, 'index'])->middleware('permission:meeting-conclusions.index,api');
Route::post('/{meeting}/conclusions', [MeetingConclusionController::class, 'store'])->middleware('permission:meeting-conclusions.store,api');
Route::put('/{meeting}/conclusions/{conclusion}', [MeetingConclusionController::class, 'update'])->middleware('permission:meeting-conclusions.update,api');
Route::delete('/{meeting}/conclusions/{conclusion}', [MeetingConclusionController::class, 'destroy'])->middleware('permission:meeting-conclusions.destroy,api');

// === Personal Notes (Ghi chú cá nhân) ===
Route::get('/{meeting}/personal-notes', [MeetingPersonalNoteController::class, 'index'])->middleware('permission:meetings.show,api');
Route::post('/{meeting}/personal-notes', [MeetingPersonalNoteController::class, 'store'])->middleware('permission:meetings.show,api');
Route::put('/{meeting}/personal-notes/{note}', [MeetingPersonalNoteController::class, 'update'])->middleware('permission:meetings.show,api');
Route::delete('/{meeting}/personal-notes/{note}', [MeetingPersonalNoteController::class, 'destroy'])->middleware('permission:meetings.show,api');

// === Speech Requests (Đăng ký phát biểu) ===
Route::get('/{meeting}/speech-requests', [MeetingSpeechRequestController::class, 'index'])->middleware('permission:meeting-speech-requests.index,api');
Route::post('/{meeting}/speech-requests', [MeetingSpeechRequestController::class, 'store'])->middleware('permission:meeting-speech-requests.store,api');
Route::patch('/{meeting}/speech-requests/{speechRequest}/approve', [MeetingSpeechRequestController::class, 'approve'])->middleware('permission:meeting-speech-requests.approve,api');
Route::patch('/{meeting}/speech-requests/{speechRequest}/reject', [MeetingSpeechRequestController::class, 'reject'])->middleware('permission:meeting-speech-requests.reject,api');
Route::delete('/{meeting}/speech-requests/{speechRequest}', [MeetingSpeechRequestController::class, 'destroy'])->middleware('permission:meeting-speech-requests.destroy,api');

Route::get('/{meeting}/votings', [MeetingVotingController::class, 'index'])->middleware('permission:meeting-votings.index,api');
Route::post('/{meeting}/votings', [MeetingVotingController::class, 'store'])->middleware('permission:meeting-votings.store,api');
Route::put('/{meeting}/votings/{voting}', [MeetingVotingController::class, 'update'])->middleware('permission:meeting-votings.update,api');
Route::delete('/{meeting}/votings/{voting}', [MeetingVotingController::class, 'destroy'])->middleware('permission:meeting-votings.destroy,api');
Route::patch('/{meeting}/votings/{voting}/open', [MeetingVotingController::class, 'open'])->middleware('permission:meeting-votings.open,api');
Route::patch('/{meeting}/votings/{voting}/close', [MeetingVotingController::class, 'close'])->middleware('permission:meeting-votings.close,api');
Route::post('/{meeting}/votings/{voting}/vote', [MeetingVotingController::class, 'vote'])->middleware('permission:meeting-votings.vote,api');
Route::get('/{meeting}/votings/{voting}/results', [MeetingVotingController::class, 'results'])->middleware('permission:meeting-votings.results,api');
